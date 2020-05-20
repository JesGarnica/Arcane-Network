<?php
namespace danixl\fac\player;

use pocketmine\Player;
use pocketmine\Server;

/*  A workaround to prevent crashes with the use of a fake player hence the 'ghost' class name.
    Has Jugador's same properties to make a perfect fake player.
*/

class Ghost implements Skeleton {

    public $kitUsed = [], $homes = [];

    private $primaryRank = false;
    private $secondaryRank = false;

    private $kills = 0;
    private $deaths = 0;
    private $coins = 0;

    private $format = false;

    private $flight = false;
    private $heal = 0;
    private $log = false;

    public $faction = "none";

    private $plugin, $server, $schedule;

    public $player, $username, $low_username;

    public function __construct(String $name) {
        $this->username = $name;
        $this->low_username = strtolower($name);
    }

    public function getChatFormat() {
        return "Ghost: ";
    }

    public function setChatFormat($format) {}

    public function playerRank($rankClass) {
        return "guest";
    }

    public function setRank($rankClass, $rank, $save = false) {}

    public function saveRanks() {}

    public function getFaction() {return false;}

    public function hasFaction() {return false;}

    public function setFaction(?String $faction, $save = true) {}

    public function isFactionLeader() {return false;}

    public function setFactionLeader(Bool $option = true) {}

    public function getFactionRank() {return 'officer';}

    public function setFactionRank($rank) {}

    public function rankUpFaction() {}

    public function rankDownFaction() {}

    public  function setFactionChatFormat(){}

    public function syncLastKitSessions() {}

    public function saveLastKitSessions() {}

    public function useKit($kit, $unit) {}

    public function isKitUsed($kit) {
        return true;
    }

    public function rmKitUsed($kit) {}

    public function getAllKitsUsed() {
        return [];
    }

    public function isKitCooling($kit) {
        return true;
    }

    public function getKitUnit($kit) {
        return 0;
    }

    public function setKitUnit($kit, $unit) {}

    public function kitDataExists() {
        return false;
    }

    public function getDeaths() {
        return 0;
    }

    public function setDeaths($amount) {}

    public function addDeaths($amount) {}

    public function getKills() {
        return 0;
    }

    public function setKills($amount) {}

    public function addKills($amount) {}

    public function saveStats() {}

    public function getCoins() {
        return 0;
    }

    public function addCoins($amount) {}

    public function takeCoins($amount) {}

    public function setCoins($amount) {}

    public function saveCoins() {}

    public function requestTP(Player $invitee) {}

    public function respondTP($response) {}

    public function teleportPlayer() {}

    public function createHome($homeName) {}

    public function homeExists($homeName) {}

    public function delHome($homeName) {}

    public function teleportHome($homeName) {}

    public function getHomePosition($homeName){
        return Server::getInstance()->getDefaultLevel()->getSafeSpawn();
    }

    public function syncHomes() {}

    public function saveHomes() {}

    public function isFlightOn() {
        return true;
    }

    public function toggleFlight() {}

    public function isLogged() {
        return false;
    }

    public function log() {}

    public function delLog() {}

    public function getHealUsage() {
        return 0;
    }

    public function setHealUsage(int $amount) {}

    public function saveHealUsage() {}

    public function syncHealUsage() {}
}