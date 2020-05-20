<?php

declare(strict_types=1);

namespace danixl\arcane\player;

use danixl\arcane\Main;

use pocketmine\Player;

class Jugador {

    private $plugin;

    private $primaryRank = false;
    private $secondaryRank = false;

    private $kills = 0;
    private $deaths = 0;
    private $coins = 0;

    private $format = false;

    private $player, $username, $low_username, $server, $schedule;

    public function __construct(Main $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->username = $player->getName();
        $this->low_username = strtolower($player->getName());
        $this->server = $this->plugin->getServer();
        $this->schedule = $this->plugin->getScheduler();
    }

    public function __destruct() {
        $this->saveRanks();
        $this->saveCoins();
        $this->saveStats();
    }

    public function getChatFormat() {
        if($this->format == false) {
            $this->format = $this->username . ":";
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
        $this->format = $this->plugin->getRank()->createFormat($this->player);
        if($save == true) $this->saveRanks();
    }

    public function saveRanks() {
        if($this->primaryRank !== false && $this->secondaryRank !== false) {
            $primary = $this->primaryRank;
            $secondary = $this->secondaryRank;
            $this->plugin->queryTask("saveRanks", [$primary, $secondary], $this->username);
        }
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
        if(is_int($amount)) {
            $current = $this->coins;
            $this->coins = $current + $amount;
        }
    }

    public function takeCoins($amount) {
        if(is_int($amount)) {
            $current = $this->coins;
            if($current > 0) {
                $this->coins = $current - $amount;
            }
        }
    }

    public function setCoins($amount) {
        if(is_int($amount)) {
            $this->coins = $amount;
        }
    }

    public function saveCoins() {
        $username = $this->player->getName();
        $coins = $this->coins;
        $this->plugin->queryTask("saveCoins", [$coins], $username);
    }
}     