<?php

namespace danixl\fac\task;

use pocketmine\scheduler\AsyncTask;

use pocketmine\Server;

use mysqli;

class onQueryTask extends AsyncTask {
    
    private $getMethod, $getData, $getPlayer, $getMySQL, $log, $log1;

	public function __construct($method, $data, $player = null) {
		$this->getMethod = $method;
		$this->getData = $data;
		$this->getPlayer = $player;
		$this->getMySQL = [
            'hostname' => 'play.arcn.us',
            'username' => 'arcane_dg',
            'password' => 'Dannyx0308@',
            'database' => 'arcane_netw'
        ];
	}
	
	public function onRun() {
		$mysql_hostname = $this->getMySQL["hostname"];
		$mysql_user = $this->getMySQL["username"];
		$mysql_password = $this->getMySQL["password"];
		$mysql_database = $this->getMySQL["database"];
		$db = new mysqli($mysql_hostname, $mysql_user, $mysql_password, $mysql_database, 3306);
		/* MYSQL CON */
		switch($this->getMethod) {
			case "syncKD":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$res = $db->query("SELECT kills FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				$res1 = $db->query("SELECT deaths FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				if($res & $res1) {
					$this->log = $res["kills"];
					$this->log1 = $res1["deaths"];
				}else{
					$this->log = false;
				}
			break;
			
			case "saveKD":
			    $username = $this->getPlayer;
				$low_username = strtolower($username);
				$kills = $this->getData[0];
				$deaths = $this->getData[1];
				$res = $db->query("UPDATE arcane_players SET kills='$kills' WHERE username='$low_username';");
				$res1 = $db->query("UPDATE arcane_players SET deaths='$deaths' WHERE username='$low_username';");
				if($res && $res1) {
				    $this->log = true;
				}
				else{
					$this->log = false;
				}
			break;
			
			case "syncRanks":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$res = $db->query("SELECT rank_p FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				$res1 = $db->query("SELECT rank_s FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				if($res && $res1) {
					$this->log = $res["rank_p"];
					$this->log1 = $res1["rank_s"];
				}
				else{
					$this->log = false;
				}
			break;
			
			case "saveRanks":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$primary = $this->getData[0];
				$secondary = $this->getData[1];
				
				$res = $db->query("UPDATE arcane_players SET rank_p='$primary' WHERE username='$low_username';");
				$res1 = $db->query("UPDATE arcane_players SET rank_s='$secondary' WHERE username='$low_username';");
				if($res && $res1) {
					$this->log = true;
				}
				else{
					$this->log = false;
				}
			break;
			
			case "syncCoins":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$res = $db->query("SELECT coins FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				if($res) {
					$this->log = $res["coins"];
				}
				else{
					$this->log = false;
				}
			break;
			
			case "saveCoins":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$coins = $this->getData[0];
				$res = $db->query("UPDATE arcane_players SET coins='$coins' WHERE username='$low_username';");
				if($res) {
					$this->log = true;
				}
				else{
					$this->log = false;
				}
			break;

            case "saveFaction":
                $username = $this->getPlayer;
                $low_username = strtolower($username);
                $faction = $this->getData;
                if($faction == NULL) {
                    $res = $db->query("UPDATE arcane_players SET faction=NULL WHERE username='$low_username';");
                }
                else {
                    $res = $db->query("UPDATE arcane_players SET faction='$faction' WHERE username='$low_username';");
                }
                if($res) {
                    $this->log = true;
                }
                else{
                    $this->log = false;
                }
                break;

            case "syncFaction":
                $username = $this->getPlayer;
                $low_username = strtolower($username);
                $res = $db->query("SELECT faction FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
                if($res) {
                    $this->log = $res["faction"];
                    var_dump($this->log);
                }
                else{
                    $this->log = false;
                }
                break;

			case "playerRegistered":
				$username = $this->getPlayer;
				$low_username = strtolower($username);
				$res = $db->query("SELECT username FROM arcane_players WHERE username='$low_username';")->fetch_assoc();
				if($res) {
				    $this->log = "account_exists";
				    return true;
				}
				$defaultRank = $this->getData;
				$res = $db->query("INSERT INTO arcane_players (username, rank_p, rank_s, coins, deaths, kills, faction) VALUES ('$username', '$defaultRank', 'unknown', '100', '0', '0', NULL);");
				if($res) {
				    $this->log = "new_account";
				}
				else {
				    $this->log = false;
				}
			break;

            case "deletePlayerFac":
                $username = $this->getData[0];
                $low_username = strtolower($username);
                $res = $db->query("UPDATE arcane_players SET faction=NULL WHERE username='$low_username';");
                if($res) {
                    $this->log = $username;
                }
                else {
                    $this->log = false;
                }
                break;
			
			default:
				$this->log = "Query Method doesn't exist.";
			break;
		}
	}
	
	public function onCompletion(Server $server) {
		switch($this->getMethod){
			case "syncKD":
				$server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log, $this->log1);
			break;
			
			case "syncRanks":
				$server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log, $this->log1);
			break;

            case "syncFaction":
                $server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log);
                break;
			
			case "syncCoins":
				$server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log);
			break;
			
			case "saveRanks":
				$server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log, $this->log1);
			break;
			
			case "playerRegistered":
				$server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log);
			break;

            case "deletePlayerFac":
                $server->getPluginManager()->getPlugin("ArcaneFA")->queryTaskDone($this->getMethod, $this->getPlayer, $this->log);
                break;
		}
	}
}