<?php

declare(strict_types=1);

namespace danixl\arcade\player;

use danixl\arcade\Arcade;
use pocketmine\Player;
use pocketmine\utils\Config;

class Jugador {

    private $primaryRank = false;
    private $secondaryRank = false;

    private $kills = 0;
    private $deaths = 0;
    private $coins = 0;

    private $format = false;

    private $flight = false;
    private $heal = 0;

    private $gameId = false, $gamemode = false, $team = false;

    private $player, $username, $low_username, $server, $schedule;

    public function __construct(Arcade $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->username = $player->getName();
        $this->low_username = strtolower($player->getName());
        $this->server = $this->plugin->getServer();
        $this->server = $this->plugin->getServer();
        $this->schedule = $this->plugin->getScheduler();
        //$this->syncHealUsage();
    }

    public function __destruct() {
        $this->saveRanks();
        $this->saveCoins();
        $this->saveStats();
    }

    public function purgeGameData(): void {
        if($this->gameId) {             
            if($this->plugin->getManager()->gameExists($this->gameId)) {
                $game = $this->plugin->getManager()->getGame($this->gameId);
                if($this->team) {
                    if($game->gameName() == "CTF") {
                        $game->updateTeamFormat($this->player);
                        $msg = $this->username. " left the match.";
                        $game->restoreOpposingTeamFlag($this->team, $this->username, $msg);
                    }
                    $game->removeTeamCount($this->team);
                }
                $this->plugin->getManager()->getGame($this->gameId)->removePlayerQueue($this->player);
                $this->plugin->getManager()->getGame($this->gameId)->removePlayer($this->player);
            }
        }
        $this->gameId = false;
        $this->gamemode = false;
        $this->team = false;
    }

    public function hasId(): bool {
        if($this->gameId) {
            return true;
        } else return false;
    }

    public function getId() {
        if($this->gameId) {
            return $this->gameId;
        }
    }

    public function setId($id): void {
        $this->gameId = $id;
    }

    public function getChatFormat() {
        if(!$this->format) {
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
        if($this->gameId) {
            if($this->team) {
                if($this->plugin->getManager()->gameExists($this->gameId)) {
                    $game = $this->plugin->getManager()->getGame($this->gameId);
                    if($game->gameName() == "CTF") {
                        $game->updateTeamFormat($this->player, $this->team);
                    }
                }
            }
        }
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

    public function getGamemode() {
        return $this->gamemode;
    }

    public function setGamemode($gamemodeName): void {
        $this->gamemode = $gamemodeName;
    }

    public function getTeam() {
        return $this->team;
    }

    public function setTeam($team) {
        $this->team = $team;
    }

    public function hasTeam() {
        if($this->team) return true;
    }
}     