<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 3/7/19
 * Time: 9:10 PM
 */

namespace danixl\fac\faction;

use danixl\fac\Main;

use pocketmine\level\Position;

class Faction {

    private $manager;

    private $possibleQueries = [];

    private $dataList = [];

    private $faction;
    private $tier = 1;

    private $leader = false;

    private $motd = false;

    private $allies = [];
    private $enemies = [];

    private $coins = 0;

    private $power = 0;

    // Secondary aka not as important
    private $home = null;

    private $members = [];

    public function __construct(FactionManager $manager, String $factionName, Array $data) {
        $this->manager = $manager;
        $this->possibleQueries = [
            "changeLeader",
            "setTier",
            "setMOTD",
            "setAlly",
            "removeAlly",
            "setEnemy",
            "removeEnemy",
            "setCoins",
            "setPower",
            "setHome",
            "delHome",
            "setMember",
            "removeMember"
        ];
        $this->dataList = [
            'tier' => 0, // Int
            'leader' => 'fadeddanix', // String
            'allies' => NULL, // Serialized Array
            'enemies' => NULL,
            'motd' => 'motd_not_found',
            'coins' => 0,
            'power' => 0,
            'home' => NULL,
            'members' => NULL
        ];
        $newData = $this->verifiedData($data);
        $this->faction = $factionName;
        $this->tier = $newData['tier'];
        $this->leader = $newData['leader'];
        $this->motd = $newData['motd'];
        $this->allies = $this->filterKey($newData['allies']);
        $this->enemies = $this->filterKey($newData['enemies']);
        $this->coins = $newData['coins'];
        $this->power = $newData['power'];
        $home = $this->filterKey($newData['home']);
        if($home == NULL) {
            $this->home = NULL;
        }
        else {
            $lvl = Main::getInstance()->getServer()->getLevelByName($home[3]);
            $position = new Position($home[0], $home[1], $home[2], $lvl);
            $this->home = $position;
        }
        $this->members = $this->filterKey($newData['members']);
    }

    private function verifiedData(Array $data) {
        foreach($this->dataList as $k => $v) {
            if(!isset($data[$k])) {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    private function filterKey($value) {
        if($value !== NULL) {
            if(!is_array($value)) {
                $decode = json_decode($value, true);
                if($decode !== false) {
                    $value = $decode;
                }
            }
        }
        return $value;
    }

    public function getName(): String {
        return $this->faction;
    }

    public function getLeader(): String {
        return $this->leader;
    }

    public function changeLeader(String $leader){
        $this->leader = $leader;
        $this->saveData("changeLeader", $leader);
    }

    public function getTier(): Int {
        return $this->tier;
    }

    public function setTier(Int $tier) {
        $this->tier = $tier;
        $this->saveData("setTier", $tier);
    }

    public function upgradeTier() {
        $this->tier =+ 1;
        $this->saveData("setTier", $this->tier);
    }

    public function getMOTD(): String {
        return $this->motd;
    }

    public function setMOTD(String $motd) {
        $this->motd = $motd;
        $this->saveData("setMOTD", $motd);
    }

    public function getAllies() {
        return $this->allies;
    }

    public function setAllies($allies) {
        $this->allies = $allies;
        $this->saveData("setAllies", $this->allies);
    }

    public function isAlly(String $faction) {
        if(isset($this->allies[$faction])) {
            return true;
        }
        else {
            return false;
        }
    }

    public function addAlly(String $faction) {
        if(!isset($this->allies[$faction])) {
            $this->allies = $faction;
            $this->saveData("setAllies", $this->allies); // Takes the new ally array
        }
        return false;
    }

    public function removeAlly(String $faction) {
        if(isset($this->allies[$faction])) {
            unset($this->allies[$faction]);
            $this->saveData("setAllies", $this->allies);
        }
        return false;
    }

    public function getEnemies() {
        return $this->enemies;
    }

    public function setEnemies($enemies) {
        $this->enemies = $enemies;
        $this->saveData("setEnemies", $this->enemies);
    }

    public function isEnemy(String $faction) {
        if(isset($this->enemies[$faction])) {
            return true;
        }
        else {
            return false;
        }
    }

    public function addEnemy(String $faction) {
        if(!isset($this->enemies[$faction])) {
            $this->enemies = $faction;
            $this->saveData("setEnemies", $this->enemies);
        }
        return false;
    }

    public function removeEnemy(String $faction) {
        if(isset($this->enemies[$faction])) {
            unset($this->enemies[$faction]);
            $this->saveData("setEnemies", $this->enemies);
        }
        return false;
    }

    public function getPower() {
        return $this->power;
    }

    public function setPower(Int $power) {
        $this->power = $power;
        $this->saveData("setPower", [$power]);
    }

    public function addPower(Int $power) {
        $this->power += $power;
        $this->saveData("setPower", [$this->power]);
    }

    public function removePower(Int $power) {
        $this->power -= $power;
        $this->saveData("setPower", [$this->power]);
    }

    public function getCoins() {
        return $this->coins;
    }

    public function setCoins(Int $coins) {
        $this->coins = $coins;
        $this->saveData("setCoins", [$coins]);
    }

    public function addCoins(Int $amount) {
        $this->coins += $amount;
        $this->saveData("setCoins", [$this->coins]);
    }

    public function removeCoins(Int $amount) {
        $this->coins -= $amount;
        $this->saveData("setCoins", $this->coins);
    }

    public function getHome() {
        return $this->home;
    }

    public function homeExists() {
        if($this->home) {
            return true;
        }
        return false;
    }

    public function setHome(Position $position) {
        $this->home = $position;
        $x = $position->getX();
        $y = $position->getY();
        $z = $position->getZ();
        $lvl = $position->getLevel()->getFolderName();
        $saveData = [
            round($x, 2),
            round($y, 2),
            round($z, 2),
            $lvl
        ];
        $this->saveData("setHome", $saveData);
    }

    public function delHome() {
        $this->home = null;
        $this->saveData("setHome", NULL);
    }

    public function getMembers() {
        return $this->members;
    }

    public function isMember($username) {
        $username = strtolower($username);

        if(isset($this->members[$username])) {
            return true;
        }
        return false;
    }

    public function getMemberRank($username) {
        $username = strtolower($username);

        if(isset($this->members[$username])) {
            return $this->members[$username];
        }
        return false;
    }

    public function setMembers($members) {
        $this->members = $members;
    }

    public function setMember($username, $rank) {
        $username = strtolower($username);
        $rank = strtolower($rank);

        if($this->manager->isRank($rank)) {
            if(!isset($this->members[$username])) {
                $this->members[$username] = $rank;
                $this->saveData("setMember", [$username, $rank]);
            }
            else {
                unset($this->members[$username]);
                $this->members[$username] = $rank;
                $this->saveData("setMember", [$username, $rank]);
            }
        }
    }

    public function removeMember($username) {
        $username = strtolower($username);

        if(isset($this->members[$username])) {
            unset($this->members[$username]);
            $this->saveData("removeMember", [$username]);
        }
    }

    public function saveData($query, $data) {
        // Most queries names are loosely or completely related to the methods that trigger them.
        // No serialization is done in this method. Serialization is done by FactionQueryTask
        if(in_array($query, $this->possibleQueries)) {
            $this->manager->queryTask($query, $data, $this->faction);
        }
        else {
            print_r("Query " . $query . " doesn't exist.");
        }
    }
}