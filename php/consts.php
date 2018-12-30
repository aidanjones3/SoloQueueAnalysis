<?php

  # Depcreated in API V4
  # const API_URL_PLATFORM_4 = "https://{region}.api.riotgames.com/lol/platform/v4/";
  const API_URL_CHAMPION_MASTERY_4 = "https://{reigon}.api.riotgames.com/lol/champion-mastery/v4/";
  const API_URL_SPECTATOR_4 = 'https://{region}.api.riotgames.com/lol/spectator/v4/';

  # Deprecated in API V4 :(
  # const API_URL_STATIC_3 = 'https://{region}.api.riotgames.com/lol/static-data/v3/';
  const API_URL_MATCH_4 = 'https://{region}.api.riotgames.com/lol/match/v4/';
  const API_URL_LEAGUE_4 = 'https://{region}.api.riotgames.com/lol/league/v4/';
  const API_URL_SUMMONER_4 = 'https://{region}.api.riotgames.com/lol/summoner/v4/';
  const API_KEY = 'RGAPI-e95becb1-98b4-4339-a645-70092fcf124a';

  // Rate limit for 10 minutes
  const LONG_LIMIT_INTERVAL = 600;
  const RATE_LIMIT_LONG = 500;

  // Rate limit for 10 seconds'
  const SHORT_LIMIT_INTERVAL = 10;
  const RATE_LIMIT_SHORT = 10;

  // Cache variables
  const CACHE_LIFETIME_MINUTES = 60;

  // Whether or not you want returned queries to be JSON or decoded JSON.
  // honestly I think this should be a public variable initalized in the constructor, but the style before me seems definitely to use const's.
  // Remove this commit if you want. - Ahubers
  const DECODE_ENABLED = TRUE;


  // Used to determine when the database was last updated for a certain accountId
  // Currently set at 1 hour (if last update time - current time is greater than 1 hour)
  const EPOCH_TIME = 3600000;

?>
