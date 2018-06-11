<?php

define('STEAM_WEB_API_KEY', '71C82B35696809EAF21A874F76928478');
define('FORTNITE_API_KEY', '68dd1af2-384a-4e14-a4f8-5045d7a3a200');
define('PUBG_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiIyNjE1ZThhMC00ZWE4LTAxMzYtMWExNi0zNzMyZGJjOWFlZTYiLCJpc3MiOiJnYW1lbG9ja2VyIiwiaWF0IjoxNTI4NjEzMjg0LCJwdWIiOiJibHVlaG9sZSIsInRpdGxlIjoicHViZyIsImFwcCI6ImxldC1zLXBsYXkifQ.YjhbzDS2GLWu6JeQHiM84hUe9YQJqvCss1prKJhFzig');

define('CSGO', 'csgo');
define('FORTNITE', 'fort');
define('PUBG', 'pubg');
define('CoC', 'coc');
define('CR', 'cr');
define('DOTA2', 'dota2');
define('LoL', 'lol');

define('STEAMID_URL', 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key='.STEAM_WEB_API_KEY.'&vanityurl=');
define('STEAM_PROFILE_URL', ' http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.STEAM_WEB_API_KEY.'&steamids=');
define('CSGO_STATS_URL', 'http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=730&key='.STEAM_WEB_API_KEY.'&steamid=');
define('FORTNITE_STATS_URL', 'https://api.fortnitetracker.com/v1/profile/');

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