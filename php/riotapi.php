<?php

  include_once("consts.php");

  class riotapi
  {
    private $cache;
    private $REGION;

    private $responseCode;
    private static $errorCodes = array(0    => 'NO_RESPONSE',
                                       400  => 'BAD_REQUEST',
                                       401  => 'UNAUTHORIZED',
                                       404  => 'NOT_FOUND',
                                       429  => 'RATE_LIMIT_EXCEEDED',
                                       500  => 'SERVER_ERROR',
  									                   503  => 'UNAVAILABLE');


    public function __construct($region, CacheInterface $cache = null)
    {
      $this->REGION = $region;
      $this->shortLimitQueue = new SplQueue();
      $this->longLimitQueue = new SplQueue();
      $this->cache = $cache;
    }

    public function getSummonerInformation($summonerId)
    {
      $call = 'summoners/by-name/' . $summonerId;

		  //add API URL to the call
		  $call = API_URL_SUMMONER_4 . $call;
		  return $this->request($call);

    }

    // __________________________________________________________________________
    // UTILITY FUNCTIONS
    // Below are functions defined that are relied upon by most of the api requests

    private function updateLimitQueue($queue, $interval, $call_limit)
    {
  		while(!$queue->isEmpty())
      {

  			/* Three possibilities here.
  			1: There are timestamps outside the window of the interval,
  			which means that the requests associated with them were long
  			enough ago that they can be removed from the queue.
  			2: There have been more calls within the previous interval
  			of time than are allowed by the rate limit, in which case
  			the program blocks to ensure the rate limit isn't broken.
  			3: There are openings in window, more requests are allowed,
  			and the program continues.*/
  			$timeSinceOldest = time() - $queue->bottom();

  			// I recently learned that the "bottom" of the
  			// queue is the beginning of the queue. Go figure.
  			// Remove timestamps from the queue if they're older than
  			// the length of the interval
  			if($timeSinceOldest > $interval){
  					$queue->dequeue();
  			}

  			// Check to see whether the rate limit would be broken; if so,
  			// block for the appropriate amount of time
  			elseif($queue->count() >= $call_limit){
  				if($timeSinceOldest < $interval){ //order of ops matters
  					echo("sleeping for".($interval - $timeSinceOldest + 1)." seconds\n");
  					sleep($interval - $timeSinceOldest);
  				}
  			}
  			// Otherwise, pass through and let the program continue.
  			else {
  				break;
  			}
  		}
  		// Add current timestamp to back of queue; this represents
  		// the current request.
  		$queue->enqueue(time());
  	}


    // Request Function --> Is the function that takes in the URL formed and

    // Makes the request to Riot's API
    private function request($call, $static = false)
    {
  		//format the full URL
      $url = $this->format_url($call);

  		//echo $url;

  		// Caching Methodology
  		if($this->cache !== null && $this->cache->has($url)){
  			$result = $this->cache->get($url);
  		}
      else
      {
  			// Check rate-limiting queues if this is not a static call.
  			if (!$static)
        {
  				$this->updateLimitQueue($this->longLimitQueue, LONG_LIMIT_INTERVAL, RATE_LIMIT_LONG);
  				$this->updateLimitQueue($this->shortLimitQueue, SHORT_LIMIT_INTERVAL, RATE_LIMIT_SHORT);
  			}

  			//call the API and return the result
  			$ch = curl_init($url);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  				'X-Riot-Token: '. API_KEY
  				));

  			$result = curl_exec($ch);
  			$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  			curl_close($ch);

  			if($this->responseCode == 200)
        {
  				if($this->cache !== null)
          {
  					$this->cache->put($url, $result, CACHE_LIFETIME_MINUTES * 60);
  				}
  			}
        else
        {
  				throw new Exception(self::$errorCodes[$this->responseCode]);
  			}
  		}

  		if (DECODE_ENABLED)
      {
  			$result = json_decode($result, true);
  		}

  		return $result;
  	}

    // Multiple Requests in One
    private function requestMultiple($calls)
    {

  		$urls=array();
  		$results=array();

  		foreach($calls as $k=>$call)
      {
  			$url = $this->format_url($call);

  			//Put cached data in results and urls to call in urls
  			if($this->cache !== null && $this->cache->has($url))
        {
  				if (self::DECODE_ENABLED)
          {
  					$results[$k] = json_decode($this->cache->get($url), true);
  				}
          else
          {
  					$results[$k] = $this->cache->get($url);
  				}

  			}
        else
        {
  				$urls[$k] = $url;
  			}
  		}

  		$callResult=$this->multiple_threads_request($urls);

  		foreach($callResult as $k=>$result)
      {
  			if($this->cache !== null)
        {
  				$this->cache->put($urls[$k], $result, self::CACHE_LIFETIME_MINUTES * 60);
  			}
  			if (self::DECODE_ENABLED)
        {
  				$results[$k] = json_decode($result, true);
  			}
        else
        {
  				$results[$k] = $result;
  			}
  		}

  		return array_merge($results);
    }

    //creates a full URL you can query on the API
    private function format_url($call)
    {
      return str_replace('{region}', $this->REGION, $call);
    }

    public function getLastResponseCode()
    {
      return $this->responseCode;
    }

    public function debug($message)
    {
      echo "<pre>";
      print_r($message);
      echo "</pre>";
    }

    public function setPlatform($region)
    {
      $this->REGION = $region;
    }

    private function multiple_threads_request($nodes)
    {
      $mh = curl_multi_init();
      $curl_array = array();
      foreach($nodes as $i => $url)
      {
        $curl_array[$i] = curl_init($url);
        curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, array(
          'X-Riot-Token: '. API_KEY
          ));
        curl_multi_add_handle($mh, $curl_array[$i]);
      }
      $running = NULL;
      do
      {
        usleep(10000);
        curl_multi_exec($mh,$running);
      } while($running > 0);

      $res = array();
      foreach($nodes as $i => $url)
      {
        $res[$i] = curl_multi_getcontent($curl_array[$i]);
      }

      foreach($nodes as $i => $url)
      {
        curl_multi_remove_handle($mh, $curl_array[$i]);
      }
      curl_multi_close($mh);
      return $res;
    }

  }







 ?>
