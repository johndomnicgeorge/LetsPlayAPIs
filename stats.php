<?php

require_once 'connection.php';

class Stats {
    
    function __construct() {
    }
    
    function checkString($val) {
        if (is_null($val)) {
            return 'N/A';
        }
        return strval($val);
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
    
    function getSteamProfileName($steamid, $fallback) {
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
        return ($fallback === null ? $steamid : $fallback);
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
                    array_push($csgo_stats, array('key' => 'Kills', 'value' => $this->checkString($kills)));
                    if ($kills !== '' && $deaths !== '') {
                        $kd = floatval($kills) / floatval($deaths);
                        array_push($csgo_stats, array('key' => 'Kills/Death', 'value' => $this->checkString(round($kd, 2))));
                    }
                } else if ($stat->name == 'total_deaths') {
                    $deaths = $stat->value;
                    array_push($csgo_stats, array('key' => 'Deaths', 'value' => $this->checkString($deaths)));
                    if ($kills !== '' && $deaths !== '') {
                        $kd = floatval($kills) / floatval($deaths);
                        array_push($csgo_stats, array('key' => 'Kills/Death', 'value' => $this->checkString(round($kd, 2))));
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
                    array_push($csgo_stats, array('key' => 'Time Played', 'value' => $this->checkString(round($time, 2)).$unit));
                } else if ($stat->name == 'total_mvps') {
                    array_push($csgo_stats, array('key' => 'MVPs', 'value' => $this->checkString($stat->value)));
                } else if ($stat->name == 'total_matches_won') {
                    $matches_won = $stat->value;
                    array_push($csgo_stats, array('key' => 'Matches Won', 'value' => $this->checkString($matches_won)));
                    if ($matches_won !== '' && $matches_played !== '') {
                        $win = floatval($matches_won) / floatval($matches_played);
                        array_push($csgo_stats, array('key' => 'Win %', 'value' => $this->checkString($win)));
                    }
                } else if ($stat->name == 'total_matches_played') {
                    $matches_played = $stat->value;
                    array_push($csgo_stats, array('key' => 'Matches Played', 'value' => $this->checkString($matches_played)));
                    if ($matches_won !== '' && $matches_played !== '') {
                        $win = 100 * floatval($matches_won) / floatval($matches_played);
                        array_push($csgo_stats, array('key' => 'Win %', 'value' => $this->checkString(round($win, 2)).' %'));
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
    
    function getPUBGStats($platform) {
        $options = array('http'=>array(
	            'method'=>"GET",
	            'header'=>'Authorization: Bearer '.PUBG_API_KEY.'\r\n'
	                        .'accept: application/vnd.api+json'.'\r\n'
	                        .'content-type: application/vnd.api+json'.'\r\n'
	           ));
        $context = stream_context_create($options);
        $seasons_file = file_get_contents(sprintf(PUBG_SEASONS_URL, rawurlencode($platform)), false, $context);
        
        if ($seasons_file === FALSE) {
            return null;
        }
        
        $seasons_json = json_decode($seasons_file, true);
        
        if ($seasons_json === null) {
            return null;
        }
        
        $season_id = '';
        
        foreach($seasons_json->data as $data) {
            $att = $data->attributes;
            if ($att->isCurrentSeason) {
                $season_id = $data->id;
                break;
            }
        }
        
        echo $season_id;
        return null;
    }
    
    function getCoCStats($tag) {
        $options = array('http'=>array(
	            'method'=>"GET",
	            'header'=>'Authorization: Bearer '.COC_API_KEY));
        $context = stream_context_create($options);
        $coc_file = @file_get_contents(COC_STATS_URL.rawurlencode($tag), false, $context);
        
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
                array('key' => 'Town Hall Level', 'value' => $this->checkString($coc_json['townHallLevel'])),
                array('key' => 'Experience Level', 'value' => $this->checkString($coc_json['expLevel'])),
                array('key' => 'Trophies', 'value' => $this->checkString($coc_json['trophies'])),
                array('key' => 'Best Trophies', 'value' => $this->checkString($coc_json['bestTrophies'])),
                array('key' => 'War Stars', 'value' => $this->checkString($coc_json['warStars'])),
                array('key' => 'Attack Wins', 'value' => $this->checkString($coc_json['attackWins'])),
                array('key' => 'Defense Wins', 'value' => $this->checkString($coc_json['defenseWins'])),
                array('key' => 'Builder Hall Level', 'value' => $this->checkString($coc_json['builderHallLevel'])),
                array('key' => 'Versus Trophies', 'value' => $this->checkString($coc_json['versusTrophies'])),
                array('key' => 'Best Versus Trophies', 'value' => $this->checkString($coc_json['bestVersusTrophies'])),
                array('key' => 'Versus Battle Wins', 'value' => $this->checkString($coc_json['versusBattleWins']))
            );
            
            return $coc_stats;
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    function getCRStats($tag) {
        $options = array('http'=>array(
	            'method'=>"GET",
	            'header'=>'Authorization: Bearer '.CR_API_KEY));
        $context = stream_context_create($options);
        $cr_file = @file_get_contents(CR_STATS_URL.rawurlencode($tag), false, $context);
        
        if ($cr_file === FALSE) {
            return null;
        }
        
        $cr_json = json_decode($cr_file, true);
        
        if ($cr_json === null) {
            return null;
        }
        
        try {
            $cr_stats = array(
                'name' => $cr_json['name'],
                array('key' => 'Rank', 'value' => $this->checkString($cr_json['rank'])),
                array('key' => 'Level', 'value' => $this->checkString($cr_json['stats']['level'])),
                array('key' => 'Trophies', 'value' => $this->checkString($cr_json['trophies'])),
                array('key' => 'Highest Trophies', 'value' => $this->checkString($cr_json['stats']['maxTrophies'])),
                array('key' => 'Three Crown Wins', 'value' => $this->checkString($cr_json['stats']['threeCrownWins'])),
                array('key' => 'Total Games', 'value' => $this->checkString($cr_json['games']['total'])),
                array('key' => 'Wins', 'value' => $this->checkString($cr_json['games']['wins'])),
                array('key' => 'Wins %', 'value' => $this->checkString($cr_json['games']['winsPercent'] * 100)),
                array('key' => 'Losses', 'value' => $this->checkString($cr_json['games']['losses'])),
                array('key' => 'Losses %', 'value' => $this->checkString($cr_json['games']['lossesPercent'] * 100)),
                array('key' => 'Draws', 'value' => $this->checkString($cr_json['games']['draws'])),
                array('key' => 'Draws %', 'value' => $this->checkString($cr_json['games']['drawsPercent'] * 100)),
                array('key' => 'Tournament Games', 'value' => $this->checkString($cr_json['games']['tournamentGames'])),
                array('key' => 'Tournament Cards Won', 'value' => $this->checkString($cr_json['stats']['tournamentCardsWon'])),
                array('key' => 'Challenge Max Wins', 'value' => $this->checkString($cr_json['stats']['challengeMaxWins'])),
                array('key' => 'Challenge Cards Won', 'value' => $this->checkString($cr_json['stats']['challengeCardsWon']))
            );
            
            return $cr_stats;
        } catch (Exception $e) {
            
        }
        return null;
    }
}

?>