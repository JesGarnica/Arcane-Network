<?php

namespace danixl\fac\player;

use danixl\fac\Main;

use pocketmine\Player;

use pocketmine\utils\Config;

use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class Jugador implements Skeleton {

	public $kitUsed = [], $homes = [];

	private $primaryRank = 'guest';
	private $secondaryRank = 'unknown';

	private $kills = 0;
	private $deaths = 0;
	private $coins = 0;

	private $format = false;

	private $flight = false;
	private $heal = 0;
	private $log = false;

	public $faction = null;
	public $isFactionLeader = false;
	public $factionRank = false;

	private $plugin, $server, $schedule;

	public $player, $username, $low_username;
	
	public function __construct(Main $plugin, Player $player) {
		$this->plugin = $plugin;
		$this->player = $player;
		$this->username = $player->getName();
		$this->low_username = strtolower($player->getName());
		$this->server = $this->plugin->getServer();
		$this->schedule = $this->plugin->getScheduler();
		$this->syncLastKitSessions();
		$this->syncHomes();
		$this->syncHealUsage();
	}
	
	public function __destruct() {
	    $this->saveFaction();
		$this->saveRanks();
		$this->saveCoins();
		$this->saveStats();
		$this->saveLastKitSessions();
		$this->saveHomes();
	}

	public function getChatFormat() {
		 if($this->format == false) {
			 $this->format = "○ " . $this->username . ":";
			 return $this->format;
		 }
		 else {
			 return $this->format;
		 }         
	}

	public function setChatFormat($format) {
		$this->format = $format;
	}

	// Ranks and coins are saved with MySQL

	public function playerRank($rankClass) {
		$rankClass = strtolower($rankClass);
		
		switch($rankClass) {
			case "primary":
			if($this->primaryRank == false) {
				return $this->plugin->getRank()->getPrimaryDefault();
			}
			else return $this->primaryRank;
			break;

			case "secondary":
			if($this->secondaryRank == false) {
				return "unknown";
			}
			else return $this->secondaryRank;
			break;
		}   
	}    

	public function setRank($rankClass, $rank, $save = false) {
		$rankClass = strtolower($rankClass);
		$rank = strtolower($rank);      
		
		if($rankClass == "primary") {
			$this->primaryRank = $rank;
			$this->plugin->getRank()->setRankPermissions($this->player);
		}      
		elseif($rankClass == "secondary") {
			$this->secondaryRank = $rank;
			$this->plugin->getRank()->setRankPermissions($this->player);
		}
		$this->setFactionChatFormat();
		//$this->format = $this->plugin->getRank()->createFormat($this->player);
		if($save == true) $this->saveRanks();
	}

	public function saveRanks() {
		if($this->primaryRank !== false && $this->secondaryRank !== false) {
			$primary = $this->primaryRank;
			$secondary = $this->secondaryRank;
			$this->plugin->queryTask("saveRanks", [$primary, $secondary], $this->username);
		}       
	}

	public function getFaction() {
	    return $this->faction;
    }

    public function hasFaction() {
        if($this->faction) {
            return true;
        }
        return false;
    }

    public function setFaction(?String $faction, $save = true) {
	    $this->faction = $faction;
	    if($save) $this->saveFaction();
    }

    public function isFactionLeader() {
	    if($this->isFactionLeader) {
	        return true;
        }
        return false;
    }

    public function setFactionLeader(Bool $option = true) {
	    $this->isFactionLeader = $option;
    }

    public function getFactionRank() {
	    return $this->factionRank;
    }

    public function setFactionRank($rank) {
	    switch($rank) {
            case "leader":
                $this->factionRank = "leader";
                break;

            case "commander":
                $this->factionRank = "commander";
                break;

            case "officer":
                $this->factionRank = "officer";
                break;

            case "member":
                $this->factionRank = "member";
                break;

            default:
                $this->factionRank = "member";
                break;
        }
    }

    private function getFactionRankIcon() {
        switch($this->factionRank) {
            case "leader":
                $icon = TextFormat::GRAY . "(" . TextFormat::RED . "Leader" . TextFormat::GRAY . ")" . TextFormat::WHITE;
                break;

            case "commander":
                $icon = "***";
                break;

            case "officer":
                $icon = "**";
                break;

            case "member":
                $icon = "*";
                break;

            default:
                $icon = '*';
        }
        return $icon;
    }

    public function rankUpFaction() {
	    switch($this->factionRank) {
            case "member":
                $this->factionRank = "officer";
                break;
        }
    }

    public function rankDownFaction() {
        switch($this->factionRank) {
            case "officer":
                $this->factionRank = "member";
                break;
        }
    }

    public  function setFactionChatFormat() {
        if($this->faction) {
            $this->format = $this->plugin->getRank()->createFormat($this->player, $this->getFactionRankIcon() . $this->faction);
        }
        else {
            $this->format = $this->plugin->getRank()->createFormat($this->player);
        }
    }

    public function saveFaction() {
        $this->plugin->queryTask("saveFaction", $this->faction, $this->username);
    }
	// Kit and home data is all saved locally through JSON
	
	public function syncLastKitSessions() {
		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
		if($data->exists("kits")) {
			$kits = $data->get("kits");
			foreach($kits as $k => $u) {
				$this->kitUsed[$k] = $u;
			}
			$data->remove("kits");
			$data->save();
		}
	}

	public function saveLastKitSessions() {
		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
		if(isset($this->kitUsed)) {
			$kits = $this->getAllKitsUsed();
			foreach($kits as $k) {
				$unit = $this->getKitUnit($k);
				if($unit > 5) {
					$kitTime = [$k => $unit];
					$data->set("kits", $kitTime);
					$data->save();
				}
			}
		}
	}
	
	public function useKit($kit, $unit) {
		if(!isset($this->kitUsed[$kit])) {
			$this->kitUsed[$kit] = $unit;
		}
	}
	
	public function isKitUsed($kit) {
		if(isset($this->kitUsed[$kit])) return true;
	}
	
	public function rmKitUsed($kit) {
		if(isset($this->kitUsed[$kit])) {
			unset($this->kitUsed[$kit]);
		}
	}
	
	public function getAllKitsUsed() {
		if(isset($this->kitUsed)) {
			return array_keys($this->kitUsed);
		}
	}
	
	public function isKitCooling($kit) {
		if(isset($this->kitUsed[$kit])) {
			if(is_int($this->kitUsed[$kit])) return true;
		}
	}
	
	public function getKitUnit($kit) {
		if(isset($this->kitUsed[$kit])) return $this->kitUsed[$kit];
	}
	
	public function setKitUnit($kit, $unit) {
		if(isset($this->kitUsed[$kit])) {
			unset($this->kitUsed[$kit]);
			$this->kitUsed[$kit] = $unit;
		}
	}
	
	public function kitDataExists() {
		if(isset($this->kitUsed)) return true;
	}

	public function getDeaths() {
		return $this->deaths;
	}

	public function setDeaths($amount) {
		if(is_numeric($amount)) {
			$this->deaths = $amount;
		}
	}

	public function addDeaths($amount) {
		if(is_numeric($amount)) {
			$current = $this->deaths;
			$this->deaths = $current + $amount;
		}
	}

	public function getKills() {
		return $this->kills;
	}

	public function setKills($amount) {
		if(is_numeric($amount)) {
			$this->kills = $amount;
		}
	}

	public function addKills($amount) {
		if(is_numeric($amount)) {
			$current = $this->kills;
			$this->kills = $current + $amount;
		}
	}

	public function saveStats() {
		$username = $this->player->getName();
		$kills = $this->kills;
		$deaths = $this->deaths;
		$this->plugin->queryTask("saveKD", [$kills, $deaths], $username);
	}

	public function getCoins() {
		return $this->coins;
	}

	public function addCoins($amount) {
		if(is_numeric($amount)) {
			$current = $this->coins;
			$this->coins = $current + $amount;
		}
	}

	public function takeCoins($amount) {
		if(is_numeric($amount)) {
			$current = $this->coins;
			if($current > 0) {
				$this->coins = $current - $amount;
			}
		}
	}

	public function setCoins($amount) {
		if(is_numeric($amount)) {
			$this->coins = $amount;
		}
	}

	public function saveCoins() {
		$username = $this->player->getName();
		$coins = $this->coins;
		$this->plugin->queryTask("saveCoins", [$coins], $username);
	}
	
	public function requestTP(Player $invitee) {
		$inviteeUsername = $invitee->getName();
		 
		if(isset($this->plugin->tpRequests[$inviteeUsername])) {
			$this->player->sendMessage($this->plugin->prefixDos . $inviteeUsername . " already has a request.");
		}
		else {
			$this->plugin->tpRequests[strtolower($inviteeUsername)] = $this->low_username;
			$this->player->sendMessage($this->plugin->prefix . "You have sent a teleport request to " . $inviteeUsername . "!");
			$this->player->sendMessage($this->plugin->prefix . "Request will expire when the invitee leaves the server.");
			$invitee->sendMessage($this->plugin->prefix . $this->player->getName() . " has sent you a teleport request!");
			$invitee->sendMessage($this->plugin->prefix . "Accept with /tpaccept or decline with /tpdecline.");
		}  
	}
	 
	 public function respondTP($response) {       
		 if(isset($this->plugin->tpRequests[$this->low_username])) {
			$response = strtolower($response);
			 switch($response) {
				
				 case "accept":
					 $this->teleportPlayer();
					 break;
					 
				 case "decline":
					 $requestUsername = $this->plugin->tpRequests[$this->low_username];
					 $request = $this->server->getPlayerExact($requestUsername);
					 unset($this->plugin->tpRequests[$this->low_username]);
					 $request->sendMessage($this->plugin->prefixDos . "Teleport request declined by " . $this->username . ".");
					 $this->player->sendMessage($this->plugin->prefix . "You have declined the request from " . $requestUsername . ".");
					 break;
			 }
		 }
		 else {
			 $this->player->sendMessage($this->plugin->prefix . "No teleport requests have been found.");
		 }  
	 }
	 
	 public function teleportPlayer() {    
		 if(isset($this->plugin->tpRequests[$this->low_username])) {
			 $requestUsername = $this->plugin->tpRequests[$this->low_username];
			 $request = $this->server->getPlayerExact($requestUsername);
			 $x = $this->player->x;
			 $y = $this->player->y;
			 $z = $this->player->z;
			 $level = $this->player->getLevel();
			 $request->teleport(new Position($x, $y, $z, $level));
			 $this->player->sendMessage($this->plugin->prefix . "You have accepted the teleport request from " . $requestUsername);
			 $request->sendMessage($this->plugin->prefix . $this->player->getName() . " has accepted your teleport request. Teleporting...");
			 unset($this->plugin->tpRequests[$this->low_username]);
		 }
	 }
	
	public function createHome($homeName) {
		$level = $this->player->getLevel()->getName();
		$x = round($this->player->x);
		$y = round($this->player->y);
		$z = round($this->player->z) + 1;
		$data = [$level, $x, $y, $z];  
		if(!isset($this->homes[$homeName])) {
			$this->homes[$homeName] = $data;
		}
	}

	public function homeExists($homeName) {
		if(isset($this->homes[$homeName])) return true;
	}

	public function delHome($homeName) {
		if(isset($this->homes[$homeName])) {
			unset($this->homes[$homeName]);            
		}    
	}
	
	public function teleportHome($homeName) {
		$home = $this->homes[$homeName];
		$level_name = $home[0];
		$level = $this->server->getLevelByName($level_name);
		$isLoaded = $this->server->isLevelLoaded($level_name);
		$x = $home[1];
		$y = $home[2];
		$z = $home[3];
		if($isLoaded) $this->player->teleport(new Position($x, $y, $z, $level));
	}

    public function getHomePosition($homeName) {
	    if(isset($this->homes[$homeName])) {
            $home = $this->homes[$homeName];
            $level_name = $home[0];
            $level = $this->server->getLevelByName($level_name);
            $isLoaded = $this->server->isLevelLoaded($level_name);
            $x = $home[1];
            $y = $home[2];
            $z = $home[3];
            if($isLoaded) return new Position($x, $y, $z, $level);
        }
    }

	public function syncHomes() {
		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
		if($data->exists("homes")) {
			$this->homes = $data->get("homes");
		}
	}
	
	public function saveHomes() {
        if($this->homeExists("default")) {
            $this->player->setSpawn($this->getHomePosition("default"));
        }
		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
		if($data->exists("homes")) $data->remove("homes");
		$data->set("homes", $this->homes);
		$data->save();
	}

	public function isFlightOn() {
		if($this->flight) {
			return true;
		}
	}

	public function toggleFlight() {
		if($this->flight == false) {
			$this->flight = true;
			$this->player->setAllowFlight(true);
		}
		else {
			$this->flight = false;
			$this->player->setAllowFlight(false);
		}
    }

    public function isLogged() {
    	if($this->log) {
    		return true;
    	}
    	return false;
    }

    public function log() {
    	$this->log = true;
    }

    public function delLog() {
    	$this->log = false;
    }

    public function getHealUsage() {
    	return $this->heal;
    }

    public function setHealUsage(int $amount) {
    	$this->heal = $amount;
    }

    public function saveHealUsage() {
    	if($this->player->hasPermission("arc.cmd.heal")) {
    		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
    		$data->set("heal-usage", $this->heal);
    		$data->save();
    	}
    }

    public function syncHealUsage() {
    	if($this->player->hasPermission("arc.cmd.heal")) {
    		$data = new Config($this->plugin->getDataFolder() . "players/" . $this->low_username . ".json", Config::JSON);
    		if($data->exists("heal-usage")) {
    			$this->heal = $data->get("heal-usage");
    		}
    		else {
    			$this->heal = 2;
    		}
    	}
    }
}     