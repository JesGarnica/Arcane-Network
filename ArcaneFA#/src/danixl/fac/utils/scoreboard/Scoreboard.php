<?php
declare(strict_types = 1);

/**
 *     _____                    _   _           _
 *    /  ___|                  | | | |         | |
 *    \ `--.  ___ ___  _ __ ___| |_| |_   _  __| |
 *     `--. \/ __/ _ \| '__/ _ \  _  | | | |/ _` |
 *    /\__/ / (_| (_) | | |  __/ | | | |_| | (_| |
 *    \____/ \___\___/|_|  \___\_| |_/\__,_|\__,_|
 *
 * ScoreHud, a Scoreboard plugin for PocketMine-MP
 * Copyright (c) 2018 JackMD  < https://github.com/JackMD >
 *
 * Discord: JackMD#3717
 * Twitter: JackMTaylor_
 *
 * This software is distributed under "GNU General Public License v3.0".
 * This license allows you to use it and/or modify it but you are not at
 * all allowed to sell this plugin at any cost. If found doing so the
 * necessary action required would be taken.
 *
 * ScoreHud is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License v3.0 for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 * ------------------------------------------------------------------------
 */

namespace danixl\fac\utils\scoreboard;

use danixl\arcade\Arcade;

use pocketmine\Player;

use pocketmine\utils\Config;

class Scoreboard {

    public $plugin, $server;

    public $scoreboardData;

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->loadConfigurations();
        $this->plugin->getScheduler()->scheduleRepeatingTask(new ScoreUpdateTask($this), 3 * 20);
        $this->plugin->getLogger()->info("Scoreboard enabled.");
    }

    public function loadConfigurations() {
        if(file_exists($this->plugin->getDataFolder() . "scoreboard.yml")) {
            $cfg = new Config($this->plugin->getDataFolder() . "scoreboard.yml");
            if(!$cfg->exists("server-names")) {
                $serverNames= ["servername1", "servername2", "servername3"];
                $cfg->set("server-names", $serverNames);
                $cfg->save();
            }
            if(!$cfg->exists("score-lines")) {
                $serverLines = ["Coins: {coins}", "Kills: {kills}", "Deaths: {deaths}"];
                $cfg->set("score-lines", $serverLines);
                $cfg->save();
            }
            $this->scoreboardData = $cfg->getAll();
        }
        else {
            $cfg = new Config($this->plugin->getDataFolder() . "scoreboard.yml");
            $serverNames = ["servername1", "servername2", "servername3"];
            $cfg->set("server-names", $serverNames);
            $scoreLines = ["Coins: {coins}", "Kills: {kills}", "Deaths: {deaths}"];
            $cfg->set("score-lines", $scoreLines);
            $cfg->save();
            $this->scoreboardData = $cfg->getAll();
        }
    }

    /**
     * @param Player $player
     * @param string $title
     */
    public function addScore(Player $player, string $title): void{
        ScoreFactory::setScore($player, $title);
        $this->updateScore($player);
    }

    public function removeScore(Player $player) {
        ScoreFactory::removeScore($player);
    }

    public function hasScore(Player $player) {
        return ScoreFactory::hasScore($player);
    }

    /**
     * @param Player $player
     */
    public function updateScore(Player $player): void{
        $i = 0;
        foreach($this->scoreboardData["score-lines"] as $line){
            $i++;
            if($i <= 15){
                ScoreFactory::setScoreLine($player, $i, $this->process($player, $line));
            }
        }
    }

    /**
     * @param Player $player
     * @return float|string
     */
    private function getPlayerCoins(Player $player){
        $coins = $this->plugin->getPlayer($player)->getCoins();
        return $coins;
    }

    /**
     * @param Player $player
     * @param string $class
     * @return string
     */
    private function getPlayerRank(Player $player, string $class = "primary"): string{
        $rank = $this->plugin->getPlayer($player)->playerRank($class);
        return $rank;
    }

    /**
     * @param Player $player
     * @return int|string
     */
    public function getPlayerKills(Player $player){
        $k = $this->plugin->getPlayer($player)->getKills();
        return $k;
    }

    /**
     * @param Player $player
     * @return int|string
     */
    public function getPlayerDeaths(Player $player){
        $d = $this->plugin->getPlayer($player)->getDeaths();
        return $d;
    }

    /*
     * CUSTOM GAMEMODE SPECIFIC
     */

    // Infected

    public function getInfectedZombies(Player $player) {
        $p = $this->plugin->getPlayer($player);
        if($p->hasId()) {
            $id = $this->plugin->getPlayer($player)->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->gameName() == "INF") {
                    if($game->getStatus() == 1) {
                        return $game->getTeamCount("zombie");
                    }
                    return "gameNotActive";
                }
                return "gameNotINF";
            }
            return "gameInvalid";
        }
        return "playerNoGame";
    }

    public function getInfectedSurvivors(Player $player) {
        $p = $this->plugin->getPlayer($player);
        if($p->hasId()) {
            $id = $this->plugin->getPlayer($player)->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->gameName() == "INF") {
                    if($game->getStatus() == 1) {
                        return $game->getTeamCount("survivor");
                    }
                    return "gameNotActive";
                }
                return "gameNotINF";
            }
            return "gameInvalid";
        }
        return "playerNoGame";
    }

    // Capture the Flag

    public function getBlueCaptures(Player $player) {
        $p = $this->plugin->getPlayer($player);
        if($p->hasId()) {
            $id = $this->plugin->getPlayer($player)->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->gameName() == "CTF") {
                    if($game->getStatus() == 1) {
                        return $game->getTeamScore("blue");
                    }
                    return "gameNotActive";
                }
                return "gameNotCTF";
            }
            return "gameInvalid";
        }
        return "playerNoGame";
    }

    public function getRedCaptures(Player $player) {
        $p = $this->plugin->getPlayer($player);
        if($p->hasId()) {
            $id = $this->plugin->getPlayer($player)->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->gameName() == "CTF") {
                    if($game->getStatus() == 1) {
                        return $game->getTeamScore("red");
                    }
                    return "gameNotActive";
                }
                return "gameNotCTF";
            }
            return "gameInvalid";
        }
        return "playerNoGame";
    }

    /**
     * @param Player $player
     * @param string $string
     * @return string
     */
    public function process(Player $player, string $string): string{
        $string = str_replace("{name}", $player->getName(), $string);
        $string = str_replace("{coins}", $this->getPlayerCoins($player), $string);
        $string = str_replace("{online}", count($this->server->getOnlinePlayers()), $string);
        $string = str_replace("{max_online}", $this->server->getMaxPlayers(), $string);
        $string = str_replace("{prank}", $this->getPlayerRank($player), $string);
        $string = str_replace("{srank}", $this->getPlayerRank($player, "secondary"), $string);
        $string = str_replace("{item_name}", $player->getInventory()->getItemInHand()->getName(), $string);
        $string = str_replace("{item_id}", $player->getInventory()->getItemInHand()->getId(), $string);
        $string = str_replace("{item_meta}", $player->getInventory()->getItemInHand()->getDamage(), $string);
        $string = str_replace("{item_count}", $player->getInventory()->getItemInHand()->getCount(), $string);
        $string = str_replace("{x}", intval($player->getX()), $string);
        $string = str_replace("{y}", intval($player->getY()), $string);
        $string = str_replace("{z}", intval($player->getZ()), $string);
        //$string = str_replace("{faction}", $this->getPlayerFaction($player), $string);
        $string = str_replace("{load}", $this->server->getTickUsage(), $string);
        $string = str_replace("{tps}", $this->server->getTicksPerSecond(), $string);
        $string = str_replace("{level_name}", $player->getLevel()->getName(), $string);
        $string = str_replace("{level_folder_name}", $player->getLevel()->getFolderName(), $string);
        $string = str_replace("{ip}", $player->getAddress(), $string);
        $string = str_replace("{ping}", $player->getPing(), $string);
        $string = str_replace("{kills}", $this->getPlayerKills($player), $string);
        $string = str_replace("{deaths}", $this->getPlayerDeaths($player), $string);
        $string = str_replace("{zombies}", $this->getInfectedZombies($player), $string);
        $string = str_replace("{survivors}", $this->getInfectedSurvivors($player), $string);
        $string = str_replace("{blue}", $this->getInfectedZombies($player), $string);
        //$string = str_replace("{kdr}", $this->getPlayerKillToDeathRatio($player), $string);
        return $string;
    }
}