<html>
  <body>
    <?php
      include('php/FileSystemCache.php');
      include('php/riotapi.php');

      $con = mysqli_connect("localhost:3306","root","Lonepeak1");

      if (!$con)
      {
        die('Could not connect: ' . mysqli_error());
      }
      mysqli_select_db($con,"SoloQueue");
      /*
      if (isset($_POST['fname']))
      {
        echo "Index exists \n";
      }
      */

      /* To Check If An Account ID already exists in the Database & The Last Time
         it was updated. There are three options here:

         1. The Account ID does not exists -> We then add the Summoner ID and Region,
         afterwhich the Riot API is called and we add the rest of the data.
         2. The Account ID does not exist but the last updated time is a long
         time ago. Therefore, we update the table entry, which will be used to updated
         the other tables (TO DO).
         3. The Account ID exists and was updated recently. We leave the data alone.
      */
      $milliseconds = round(microtime(true) * 1000);
      //echo "Milliseconds: $milliseconds\n";

      $sumID = $_POST['sumId'];
      $region = $_POST['platform'];
      echo nl2br("Entered a Summoner ID:" .$sumID ."\r\n");
      echo nl2br("Entered a Region:" .$region ."\r\n");


      $result = mysqli_query($con,"SELECT epoch FROM ACCOUNTID where name = '$sumID'");
      $array = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $epoch = $array['epoch'];




      if(mysqli_num_rows($result) == 0)
      {

        echo "Could not find a Summoner ID with that name in the database \r\n";

        $api = new riotapi($region, new FileSystemCache('cache/'));

        try
        {
          $r = $api->getSummonerInformation($sumID);
        }
        catch(Exception $e)
        {
          echo "Error: " . $e->getMessage();
        }

        $sql = "INSERT INTO ACCOUNTID (name, region, sumLvl, puuid, accountId, id, epoch)
        VALUES ('{$r['name']}', '$region', '{$r['summonerLevel']}', '{$r['puuid']}', '{$r['accountId']}', '{$r['id']}', $milliseconds)";

        if (!mysqli_query($con,$sql))
        {
          die('Error: ' . mysqli_error($con));
        }

        echo nl2br("Succesfully added to database.\r\n");



      }elseif($milliseconds - $epoch >= EPOCH_TIME)
      {
        echo nl2br("Found an entry, but it hasn't been updated recently \r\n");

        $api = new riotapi($region, new FileSystemCache('cache/'));

        try
        {
          $r = $api->getSummonerInformation($sumID);
        }
        catch(Exception $e)
        {
          echo "Error: " . $e->getMessage();
        }

        $sql = "UPDATE ACCOUNTID
        SET sumLvl = '{$r['summonerLevel']}', puuid = '{$r['puuid']}',
        accountId = '{$r['accountId']}', id = '{$r['id']}', epoch = $milliseconds
        WHERE name = '$sumID'";

        if (!mysqli_query($con,$sql))
        {
          die('Error: ' . mysqli_error($con));
        }

        echo nl2br("Succesfully edited the database.\r\n");

        // $sql = "UPDATE ACCOUNTID SET sumLvl = '$r['summonerLevel']', puuid "


      }elseif(mysqli_num_rows($result) > 1) {
        echo nl2br("Multiple Entries with same Summoner ID\r\n");
      }
      else{
        echo "Nothing to be done...\r\n";
      }

      mysqli_close($con);


    ?>
  </body>
</html>
