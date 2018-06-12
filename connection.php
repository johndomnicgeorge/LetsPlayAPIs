<?php

define('STEAM_WEB_API_KEY', '71C82B35696809EAF21A874F76928478');
define('FORTNITE_API_KEY', '68dd1af2-384a-4e14-a4f8-5045d7a3a200');
define('PUBG_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiIyNjE1ZThhMC00ZWE4LTAxMzYtMWExNi0zNzMyZGJjOWFlZTYiLCJpc3MiOiJnYW1lbG9ja2VyIiwiaWF0IjoxNTI4NjEzMjg0LCJwdWIiOiJibHVlaG9sZSIsInRpdGxlIjoicHViZyIsImFwcCI6ImxldC1zLXBsYXkifQ.YjhbzDS2GLWu6JeQHiM84hUe9YQJqvCss1prKJhFzig');
define('COC_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiIsImtpZCI6IjI4YTMxOGY3LTAwMDAtYTFlYi03ZmExLTJjNzQzM2M2Y2NhNSJ9.eyJpc3MiOiJzdXBlcmNlbGwiLCJhdWQiOiJzdXBlcmNlbGw6Z2FtZWFwaSIsImp0aSI6IjgxYjQ2NzNkLWNiZjEtNGQ5Yi05N2YyLThkM2QyZmY4N2M1MiIsImlhdCI6MTUyODcyMzE1MSwic3ViIjoiZGV2ZWxvcGVyLzcyM2I4OTE0LTdlMTYtM2NmZC1lOTViLTk4ODFhODVhMTAwYSIsInNjb3BlcyI6WyJjbGFzaCJdLCJsaW1pdHMiOlt7InRpZXIiOiJkZXZlbG9wZXIvc2lsdmVyIiwidHlwZSI6InRocm90dGxpbmcifSx7ImNpZHJzIjpbIjUyLjYzLjcwLjEzNCJdLCJ0eXBlIjoiY2xpZW50In1dfQ.mKYxk9opJKbPmzlaxIwmyKuQbhBLWdrBwBG45W9g1TVWLGKCeWaGYTrrkBZMqrG_QKBJ6ZOPHaNA-kcBsqMNag');
define('CR_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6ODU3LCJpZGVuIjoiNDUyMjk0MjE4ODk2MTEzNjY1IiwibWQiOnt9LCJ0cyI6MTUyODc2MzczNDQ3NH0.rRjdiB-7sVzY2xZRkldh6-O2E9H7pNuIA1aIxqYVkf4');

define('CSGO', 'csgo');
define('FORTNITE', 'fort');
define('PUBG', 'pubg');
define('CoC', 'coc');
define('CR', 'cr');
define('DOTA2', 'dota2');
define('LoL', 'lol');

define('STEAMID_URL', 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key='.STEAM_WEB_API_KEY.'&vanityurl=');
define('STEAM_PROFILE_URL', 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.STEAM_WEB_API_KEY.'&steamids=');
define('CSGO_STATS_URL', 'http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=730&key='.STEAM_WEB_API_KEY."&steamid=");
define('FORTNITE_STATS_URL', 'https://api.fortnitetracker.com/v1/profile/');
define('COC_STATS_URL', 'https://api.clashofclans.com/v1/players/');
define('CR_STATS_URL', 'https://api.royaleapi.com/player/');

class connection {
    private $conn;
	private $host = "localhost";
	private $user = "jakfreeh_jak";
	private $pass = "jak@2018";
	private $db = "jakfreeh_unitec_assignment";
 
    function __construct() {
    }
 
    public function connect() {
 
        // Connecting to mysql database
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
 
        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        // returning connection resource
        return $this->conn;
    }
}
?>