<?php

namespace danixl\fac\player;

use pocketmine\Player;

interface Skeleton {

    public function getChatFormat();

    public function setChatFormat($format);

    public function playerRank($rankClass);

    public function setRank($rankClass, $rank, $save = false);

    public function saveRanks();

    public function getFaction();

    public function hasFaction();

    public function setFaction(?String $faction, $save = true);

    public function isFactionLeader();

    public function setFactionLeader(Bool $option = true);

    public function getFactionRank();

    public function setFactionRank($rank);

    public function rankUpFaction();

    public function rankDownFaction();

    public  function setFactionChatFormat();

    public function syncLastKitSessions();

    public function saveLastKitSessions();

    public function useKit($kit, $unit);

    public function isKitUsed($kit);

    public function rmKitUsed($kit);

    public function getAllKitsUsed();

    public function isKitCooling($kit);

    public function getKitUnit($kit);

    public function setKitUnit($kit, $unit);

    public function kitDataExists();

    public function getDeaths();

    public function setDeaths($amount);

    public function addDeaths($amount);

    public function getKills();

    public function setKills($amount);

    public function addKills($amount);

    public function saveStats();

    public function getCoins();

    public function addCoins($amount);

    public function takeCoins($amount);

    public function setCoins($amount);

    public function saveCoins();

    public function requestTP(Player $invitee);

    public function respondTP($response);

    public function teleportPlayer();

    public function createHome($homeName);

    public function homeExists($homeName);

    public function delHome($homeName);

    public function teleportHome($homeName);

    public function getHomePosition($homeName);

    public function syncHomes();

    public function saveHomes();

    public function isFlightOn();

    public function toggleFlight();

    public function isLogged();

    public function log();

    public function delLog();

    public function getHealUsage();

    public function setHealUsage(int $amount);

    public function saveHealUsage();

    public function syncHealUsage();
}