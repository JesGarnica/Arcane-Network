<?php

namespace danixl\fac\faction;

use pocketmine\scheduler\AsyncTask;

use pocketmine\Server;

use mysqli;

class FactionQueryTask extends AsyncTask {
    
    private $getMethod, $getData, $getFaction, $getMySQL;

    private $log, $log1;

	public function __construct($method, $data, $faction = null) {
		$this->getMethod = $method;
		$this->getData = $data;
		$this->getFaction = $faction;
		$this->getMySQL = [
            'hostname' => '127.0.0.1',
            'username' => 'arcane_fac',
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
            case "syncFactions":
                $res = $db->query("SELECT faction FROM factions")->fetch_all(1);
                $factions = array_column($res, 'faction');
                 if($res) {
                    $this->log = serialize($factions);
                }
                else{
                    $this->log = false;
                }
                break;

            case "syncData":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $res = $db->query("SELECT * FROM factions WHERE faction='$low_faction';")->fetch_assoc();
                $data = json_encode($res);
                if($res) {
                    $this->log = $data;
                    var_dump($this->log);
                }else{
                    $this->log = false;
                }
                break;

            case "createFaction":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $res = $db->query("SELECT faction FROM factions WHERE faction='$low_faction';")->fetch_assoc();
                if($res == NULL) {
                    $leader = $this->getData;
                    $leaderLowercase = strtolower($leader);
                    $res = $db->query("INSERT INTO factions (faction, name, tier, leader, allies, enemies, motd, coins, power, home, members) VALUES ('$low_faction', '$faction', '1', '$leader', null, null, '§aA New Faction Arises', '0', '4', NULL, JSON_OBJECT('$leaderLowercase', 'leader'));");
                    if($res) {
                        $this->log = $leader;
                    }
                    else {
                        $this->log = false;
                    }
                }
                else {
                    $this->log = false;
                }
                break;

            case "changeLeader":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $username = $this->getData;
                $low_username = strtolower($username);
                $res = $db->query("UPDATE factions SET leader='$low_username' WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setMOTD":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $newMOTD = $this->getData;
                $res = $db->query("UPDATE factions SET motd='$newMOTD' WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setTier":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $tier = $this->getData;
                $res = $db->query("UPDATE factions SET tier='$tier' WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setAllies":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $allies = $this->getData;
                if($allies == NULL) {
                    $res = $db->query("UPDATE factions SET allies=NULL WHERE faction='$low_faction';");
                }
                else {
                    $allies = json_encode($allies);
                    $res = $db->query("UPDATE factions SET allies='$allies' WHERE faction='$low_faction';");
                }
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setEnemies":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $enemies = $this->getData;
                if($enemies == NULL) {
                    $res = $db->query("UPDATE factions SET enemies=NULL WHERE faction='$low_faction';");
                }
                else {
                    $enemies = json_encode($enemies);
                    $res = $db->query("UPDATE factions SET enemies='$enemies' WHERE faction='$low_faction';");
                }
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setPower":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $power = $this->getData[0];
                $res = $db->query("UPDATE factions SET power='$power' WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setCoins":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $coins = $this->getData[0];
                $res = $db->query("UPDATE factions SET coins='$coins' WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setHome":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $home = $this->getData;
                if($home == NULL) {
                    $res = $db->query("UPDATE factions SET home=NULL WHERE faction='$low_faction';");
                }
                else {
                    $home = json_encode($home);
                    $res = $db->query("UPDATE factions SET home='$home' WHERE faction='$low_faction';");
                }
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "delHome":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $res = $db->query("UPDATE factions SET home=NULL WHERE faction='$low_faction';");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "setMember":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $member = $this->getData[0];
                $rank = $this->getData[1];
                $res = $db->query("UPDATE factions SET members=JSON_SET(members, '$.$member', '$rank') WHERE faction='$low_faction'");
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

            case "removeMember":
                $faction = $this->getFaction;
                $low_faction = strtolower($faction);
                $member = $this->getData[0];
                $res = $db->query("UPDATE factions SET members=JSON_REMOVE(members, '$.$member') WHERE faction='$low_faction'");
                var_dump($res);
                if($res) {
                    $this->log = true;
                }
                else {
                    $this->log = false;
                }
                break;

			default:
				$this->log = "Query method doesn't exist.";
			break;
		}
	}
	
	public function onCompletion(Server $server) {
		switch($this->getMethod){
			case "syncFactions":
				$server->getPluginManager()->getPlugin("ArcaneFA")->getFac()->queryTaskDone($this->getMethod, $this->getFaction, $this->log);
			break;

            case "syncData":
                $server->getPluginManager()->getPlugin("ArcaneFA")->getFac()->queryTaskDone($this->getMethod, $this->getFaction, $this->log);
                break;

            case "createFaction":
                $server->getPluginManager()->getPlugin("ArcaneFA")->getFac()->queryTaskDone($this->getMethod, $this->getFaction, $this->log);
                break;
		}
	}
}