<?php

require_once 'connection.php';

class Stats {
    
    function __construct() {
    }
    
    function getSteam64ID($nickname) {
        try {
            $vanity_file = @file_get_contents(STEAMID_URL.$nickname);
            $vanity_json = json_decode($vanity_file);
            $response = $vanity_json->response;
            $success = $response->success;
            if ($success == 1) {
                return $response->steamid;
            }
            return '';
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    function getSteamProfileName($steamid) {
        try {
            $name_file = @file_get_contents(STEAM_PROFILE_URL.$steamid);
            $name_json = json_decode($name_file);
            $response = $name_json->response;
            $players = $response->players;
            if (count($players) > 0) {
                return $players[0]->personaname;
            }
        } catch (Exception $e) {
            
        }
        return $steamid;
    }
    
    function getCSGOStats($steamid) {
        $csgo_file = @file_get_contents(CSGO_STATS_URL.$steamid);
        if ($csgo_file === FALSE) {
            return null;
        }
        
        $csgo_json = json_decode($csgo_file);
        
        if ($csgo_json === null) {
            return null;
        }
        
        try {
            $csgo_stats = array();
            $matches_played = '';
            $matches_won = '';
            $kills = '';
            $deaths = '';
            $playerstats = $csgo_json->playerstats;
            foreach ($playerstats->stats as $stat) {
                if ($stat->name == 'total_kills') {
                    $kills = $stat->value;
                    array_push($csgo_stats, array('key' => 'Kills', 'value' => strval($kills)));
                    if ($kills !== '' && $deaths !== '') {
                        $kd = floatval($kills) / floatval($deaths);
                        array_push($csgo_stats, array('key' => 'Kills/Death', 'value' => strval(round($kd, 2))));
                    }
                } else if ($stat->name == 'total_deaths') {
                    $deaths = $stat->value;
                    array_push($csgo_stats, array('key' => 'Deaths', 'value' => strval($deaths)));
                    if ($kills !== '' && $deaths !== '') {
                        $kd = floatval($kills) / floatval($deaths);
                        array_push($csgo_stats, array('key' => 'Kills/Death', 'value' => strval(round($kd, 2))));
                    }
                } else if ($stat->name == 'total_time_played') {
                    $value = floatval($stat->value);
                    $unit = ' hrs';
                    $time = 0;
                    if ($value / 3600 < 1) {
                        $time = $value / 60;
                        $unit = ' min';
                    } else {
                        $time = $value / 3600;
                    }
                    array_push($csgo_stats, array('key' => 'Time Played', 'value' => strval(round($time, 2)).$unit));
                } else if ($stat->name == 'total_mvps') {
                    array_push($csgo_stats, array('key' => 'MVPs', 'value' => strval($stat->value)));
                } else if ($stat->name == 'total_matches_won') {
                    $matches_won = $stat->value;
                    array_push($csgo_stats, array('key' => 'Matches Won', 'value' => strval($matches_won)));
                    if ($matches_won !== '' && $matches_played !== '') {
                        $win = floatval($matches_won) / floatval($matches_played);
                        array_push($csgo_stats, array('key' => 'Win %', 'value' => strval($win)));
                    }
                } else if ($stat->name == 'total_matches_played') {
                    $matches_played = $stat->value;
                    array_push($csgo_stats, array('key' => 'Matches Played', 'value' => strval($matches_played)));
                    if ($matches_won !== '' && $matches_played !== '') {
                        $win = 100 * floatval($matches_won) / floatval($matches_played);
                        array_push($csgo_stats, array('key' => 'Win %', 'value' => strval(round($win, 2)).' %'));
                    }
                } 
            }
            return $csgo_stats;
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    function getFortniteStats($nickname, $platform) {
        $options = array('http'=>array(
	            'method'=>"GET",
	            'header'=>'TRN-Api-Key: '.FORTNITE_API_KEY));
        $context = stream_context_create($options);
        $fort_file = @file_get_contents(FORTNITE_STATS_URL.$platform.'/'.rawurlencode($nickname), false, $context);
        if ($fort_file === FALSE) {
            return null;
        }
        
        $fort_json = json_decode($fort_file, true);
        
        if ($fort_json === null) {
            return null;
        }
        
        try {
            
            $fort_response = array(
                // solo stats
                array('key' => 'Solo - Wins', 'value' => $fort_json['stats']['p2']['top1']['value']),
                array('key' => 'Solo - Top 10', 'value' => $fort_json['stats']['p2']['top10']['value']),
                array('key' => 'Solo - Top 25', 'value' => $fort_json['stats']['p2']['top25']['value']),
                array('key' => 'Solo - Kills/Death', 'value' => $fort_json['stats']['p2']['kd']['value']),
                array('key' => 'Solo - Matches', 'value' => $fort_json['stats']['p2']['matches']['value']),
                array('key' => 'Solo - Kills', 'value' => $fort_json['stats']['p2']['kills']['value']),
                array('key' => 'Solo - Kills/Game', 'value' => $fort_json['stats']['p2']['kpg']['value']),
                
                // duos stats
                array('key' => 'Duos - Wins', 'value' => $fort_json['stats']['p10']['top1']['value']),
                array('key' => 'Duos - Top 5', 'value' => $fort_json['stats']['p10']['top5']['value']),
                array('key' => 'Duos - Top 12', 'value' => $fort_json['stats']['p10']['top12']['value']),
                array('key' => 'Duos - Kills/Death', 'value' => $fort_json['stats']['p10']['kd']['value']),
                array('key' => 'Duos - Matches', 'value' => $fort_json['stats']['p10']['matches']['value']),
                array('key' => 'Duos - Kills', 'value' => $fort_json['stats']['p10']['kills']['value']),
                array('key' => 'Duos - Kills/Game', 'value' => $fort_json['stats']['p10']['kpg']['value']),
                
                // squad stats
                array('key' => 'Squad - Wins', 'value' => $fort_json['stats']['p9']['top1']['value']),
                array('key' => 'Squad - Top 3', 'value' => $fort_json['stats']['p9']['top3']['value']),
                array('key' => 'Squad - Top 6', 'value' => $fort_json['stats']['p9']['top6']['value']),
                array('key' => 'Squad - Kills/Death', 'value' => $fort_json['stats']['p9']['kd']['value']),
                array('key' => 'Squad - Matches', 'value' => $fort_json['stats']['p9']['matches']['value']),
                array('key' => 'Squad - Kills', 'value' => $fort_json['stats']['p9']['kills']['value']),
                array('key' => 'Squad - Kills/Game', 'value' => $fort_json['stats']['p9']['kpg']['value'])
            );
        
            return $fort_response;
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    function getPUBGStats() {
        
    }
    
    function getCoCStats($tag) {
        $options = array('http'=>array(
	            'method'=>"GET",
	            'header'=>'Authorization: Bearer '.COC_API_KEY));
        $context = stream_context_create($options);
        $coc_file = file_get_contents(COC_STATS_URL.rawurlencode($tag), false, $context);
        
        if ($coc_file === FALSE) {
            return null;
        }
        
        $coc_json = json_decode($coc_file, true);
        
        if ($coc_json === null) {
            return null;
        }
        
        try {
            $coc_stats = array(
                'name' => $coc_json['name'],
                array('key' => 'Town Hall Level', 'value' => strval($coc_json['townHallLevel'])),
                array('key' => 'Experience Level', 'value' => strval($coc_json['expLevel'])),
                array('key' => 'Trophies', 'value' => strval($coc_json['trophies'])),
                array('key' => 'Best Trophies', 'value' => strval($coc_json['bestTrophies'])),
                array('key' => 'War Stars', 'value' => strval($coc_json['warStars'])),
                array('key' => 'Attack Wins', 'value' => strval($coc_json['attackWins'])),
                array('key' => 'Defense Wins', 'value' => strval($coc_json['defenseWins'])),
                array('key' => 'Builder Hall Level', 'value' => strval($coc_json['builderHallLevel'])),
                array('key' => 'Versus Trophies', 'value' => strval($coc_json['versusTrophies'])),
                array('key' => 'Best Versus Trophies', 'value' => strval($coc_json['bestVersusTrophies'])),
                array('key' => 'Versus Battle Wins', 'value' => strval($coc_json['versusBattleWins']))
            );
            
            return $coc_stats;
        } catch (Exception $e) {
            
        }
        return null;
    }
}

?>