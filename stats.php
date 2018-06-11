<?php

require_once 'connection.php';

class Stats {
    
    function __construct() {
    }
    
    function getSteam64ID($nickname) {
        try {
            $vanity_file = @file_get_contents(STEAMID_URL.$nickname);
            $vanity_json = json_decode($vanity_file);
            $success = $vanity_json->success;
            if ($success == 1) {
                return $vanity_json->response;
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
            $players = $name_json->response['players'];
            if (count($players) > 0) {
                return $players[0]['personaname'];
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
        
        $csgo_json = json_decode($csgo_file, true);
        
        if ($csgo_json === null) {
            return null;
        }
        
        try {
            $csgo_stats = array();
            foreach ($csgo_json['playerstats']['stats'] as $stat) {
                if ($stat->name == 'total_kills') {
                    $csgo_stats['kills'] = $stat->value;
                } else if ($stat->name == 'total_deaths') {
                    $csgo_stats['deaths'] = $stat->value;
                } else if ($stat->name == 'total_time_played') {
                    $csgo_stats['time_played'] = $stat->value;
                } else if ($stat->name == 'total_wins') {
                    $csgo_stats['wins'] = $stat->value;
                } else if ($stat->name == 'total_mvps') {
                    $csgo_stats['mvps'] = $stat->value;
                } else if ($stat->name == 'total_matches_won') {
                    $csgo_stats['matches_won'] = $stat->value;
                } else if ($stat->name == 'total_matches_played') {
                    $csgo_stats['matches_played'] = $stat->value;
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
            // solo stats
            $fort_solo = array(
                'wins' => $fort_json['stats']['p2']['top1']['value'],
                'top10' => $fort_json['stats']['p2']['top10']['value'],
                'top25' => $fort_json['stats']['p2']['top25']['value'],
                'kd' => $fort_json['stats']['p2']['kd']['value'],
                'matches' => $fort_json['stats']['p2']['matches']['value'],
                'kills' => $fort_json['stats']['p2']['kills']['value'],
                'kpg' => $fort_json['stats']['p2']['kpg']['value']
            );
                
            // duos stats
            $fort_duos = array(
                'wins' => $fort_json['stats']['p10']['top1']['value'],
                'top5' => $fort_json['stats']['p10']['top5']['value'],
                'top12' => $fort_json['stats']['p10']['top12']['value'],
                'kd' => $fort_json['stats']['p10']['kd']['value'],
                'matches' => $fort_json['stats']['p10']['matches']['value'],
                'kills' => $fort_json['stats']['p10']['kills']['value'],
                'kpg' => $fort_json['stats']['p10']['kpg']['value']
            );
                  
            // squad stats 
            $fort_squad = array(
                'wins' => $fort_json['stats']['p9']['top1']['value'],
                'top3' => $fort_json['stats']['p9']['top3']['value'],
                'top6' => $fort_json['stats']['p9']['top6']['value'],
                'kd' => $fort_json['stats']['p9']['kd']['value'],
                'matches' => $fort_json['stats']['p9']['matches']['value'],
                'kills' => $fort_json['stats']['p9']['kills']['value'],
                'kpg' => $fort_json['stats']['p9']['kpg']['value']
            );
            
            $fort_response = array(
                'solo' => $fort_solo,
                'duos' => $fort_duos,
                'squad' => $fort_squad
            );
        
            return $fort_response;
        } catch (Exception $e) {
            
        }
        return null;
    }
}

?>