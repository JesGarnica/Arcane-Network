<?php

namespace danixl\arcade\listeners;

use danixl\arcade\Arcade;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\event\block\BlockBreakEvent;

use pocketmine\block\Block;

use pocketmine\tile\Sign;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class AdminListener implements Listener {

    private $plugin, $server;

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
    }

    /**
     * @param PlayerJoinEvent $event
     * @priority HIGHEST
     */

    public function adminJoin(PlayerJoinEvent $event) {
        $p = $event->getPlayer();
        $username = strtolower($p->getName());
        if(isset($this->plugin->admin[$username])) unset($this->plugin->admin[$username]);
    }

    /**
     * @param PlayerInteractEvent $event
     * @priority HIGHEST
     */

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $action = $event->getAction();
        $block = $event->getBlock();

        if(
            (
                $block->getId() == Block::SIGN_POST OR
                $block->getId() == Block::WALL_SIGN
            ) AND
            $action == PlayerInteractEvent::RIGHT_CLICK_BLOCK
        ) {
            if(isset($this->plugin->edit[$username]) && $player->hasPermission("arc.sign")) {
                $tile = $block->getLevel()->getTile($block);
                if($tile instanceof Sign) {
                    $text = $tile->getText();
                    $clean = TextFormat::clean($text[0]);
                    switch($clean) {

                        case "ldctf":
                            if(isset($text[1])) {
                                $id = $text[1];
                                if($this->plugin->getManager()->gameExists($id)) {
                                    $game = $this->plugin->getManager()->getGame($id);
                                    if($game->gameName() == "CTF") {
                                        $ln1 = "§l§cC§9T§7F";
                                        $ln2 = "§l§d" . ucfirst($game->arenaName());
                                        if($game->getStatus() == 1) {
                                            $ln3 = "[§6In§7-§6Match§0]";
                                            $ln4 = "§f" . $game->getPlayerCount() . "§0/§f12";
                                        }
                                        else {
                                            $ln3 = "[§aQueueing§0]";
                                            $ln4 = "§f" . count($game->getQueues()) . "§0/§f12";
                                        }
                                        $tile->setText($ln1, $ln2, $ln3, $ln4);
                                        $x = $block->x;
                                        $y = $block->y;
                                        $z = $block->z;
                                        $level = $block->getLevel()->getName();
                                        $cfg = new Config($this->plugin->getDataFolder() . "signs.yml", Config::YAML);
                                        $data = ['pos' => ["pos-x" => $x, "pos-y" => $y, "pos-z" => $z, "level" => $level], 'arena' => $game->arenaName(), 'gm' => "CTF"];
                                        $cfg->set(strtoupper($game->gameId()), $data);
                                        $cfg->save();
                                        $player->sendMessage($this->plugin->prefix .  "Created match sign successfully!");
                                    }
                                    else {
                                        $player->sendMessage($this->plugin->prefixDos .  "Game name doesn't match the intended game sign for CTF.");
                                    }
                                }
                                else {
                                    $player->sendMessage($this->plugin->prefixDos .  "Invalid Game ID.");
                                    $event->setCancelled();
                                }
                            }
                            else {
                                $player->sendMessage($this->plugin->prefixDos .  "CTF sign could not be created.");
                            }
                            break;

                        case "ldinf":
                            if(isset($text[1])) {
                                $id = $text[1];
                                if($this->plugin->getManager()->gameExists($id)) {
                                    $game = $this->plugin->getManager()->getGame($id);
                                    if($game->gameName() == "INF") {
                                        $ln1 = "§l§cInfected";
                                        $ln2 = "§l§d" . ucfirst($game->arenaName());
                                        if($game->getStatus() == 1) {
                                            $ln3 = "[§6In§7-§6Match§0]";
                                            $ln4 = "§f" . $game->getPlayerCount() . "§0/§f12";
                                        }
                                        else {
                                            $ln3 = "[§aQueueing§0]";
                                            $ln4 = "§f" . count($game->getQueues()) . "§0/§f12";
                                        }
                                        $tile->setText($ln1, $ln2, $ln3, $ln4);
                                        $x = $block->x;
                                        $y = $block->y;
                                        $z = $block->z;
                                        $level = $block->getLevel()->getName();
                                        $cfg = new Config($this->plugin->getDataFolder() . "signs.yml", Config::YAML);
                                        $data = ['pos' => ["pos-x" => $x, "pos-y" => $y, "pos-z" => $z, "level" => $level], 'arena' => $game->arenaName(), 'gm' => "INF"];
                                        $cfg->set(strtoupper($game->gameId()), $data);
                                        $cfg->save();
                                        $player->sendMessage($this->plugin->prefix .  "Created match sign successfully!");
                                    }
                                    else {
                                        $player->sendMessage($this->plugin->prefixDos .  "Game name doesn't match the intended game sign for Infected.");
                                    }
                                }
                                else {
                                    $player->sendMessage($this->plugin->prefixDos .  "Invalid Game ID.");
                                    $event->setCancelled();
                                }
                            }
                            else {
                                $player->sendMessage($this->plugin->prefixDos .  "Infected sign could not be created.");
                            }
                            break;

                        case "ldkit":
                            if($this->plugin->getKit()->isKit($text[1])) {
                                $tile->setText("[§l§6Class§r]", "§f" . $text[1]);
                            }
                            else {
                                $player->sendMessage($this->plugin->prefixDos .  "Kit doesn't exist.");
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     */

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());

        if(isset($this->plugin->admin[$username])) {
            $block = $event->getBlock();
            $x = $block->x;
            $y = $block->y;
            $z = $block->z;
            $level = $block->getLevel()->getName();
            if(isset($this->plugin->admin[$username]["queue"])) {
                $setArena = $this->plugin->admin[$username]["queue"][0];
                $gm = strtolower($this->plugin->admin[$username]["queue"][1]);
                $cfg = new Config($this->plugin->getDataFolder() . "arenas/" . $gm . "/" . $setArena . ".yml", Config::YAML);
                $event->setCancelled();
                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                $cfg->set("queue", $data);
                $cfg->save();
                unset($this->plugin->admin[$username]);
                $player->sendMessage($this->plugin->prefix .  "Queue room point has been set!");
                return true;
            }
            if(isset($this->plugin->admin[$username]["getpos"])) {
                $event->setCancelled();
                $player->sendMessage($this->plugin->prefix .  "Position of Block:");
                $player->sendMessage($this->plugin->prefix .  "§8- §ax§f: " . $x . ", §cy§f: " . $y . ", §9z§f: " . $z . ", §7level: " . $level);
                unset($this->plugin->admin[$username]["getpos"]);
                return true;
            }
            if(isset($this->plugin->admin[$username]["arena"])) {
                $setArena = $this->plugin->admin[$username]["arena"][0];
                $gm = strtolower($this->plugin->admin[$username]["arena"][1]);
                if(!is_dir($this->plugin->getDataFolder() . "arenas/" . $gm)) {
                    mkdir($this->plugin->getDataFolder() . "arenas/" . $gm);
                    $this->plugin->getLogger()->info("Arena directory created for: " . $gm);
                }
                $cfg = new Config($this->plugin->getDataFolder() . "arenas/" . $gm . "/" . $setArena . ".yml", Config::YAML);
                if($this->plugin->admin[$username]["arena"][1] == "CTF") {
                    if(isset($this->plugin->admin[$username]["flag"])) {
                        $flag = $this->plugin->admin[$username]["flag"];
                        switch ($flag) {

                            case "blue":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("blue-flag", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Blue flag point has been set!");
                                break;

                            case "red":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("red-flag", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Red flag point has been set!");
                                break;
                        }
                        return true;
                    }
                    if(isset($this->plugin->admin[$username]["return"])) {
                        $return = $this->plugin->admin[$username]["return"];
                        switch ($return) {

                            case "blue":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("blue-return", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Blue return point has been set!");
                                break;

                            case "red":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("red-return", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Red return point has been set!");
                                break;
                        }
                        return true;
                    }
                    if(isset($this->plugin->admin[$username]["spawn"])) {
                        $spawn = $this->plugin->admin[$username]["spawn"];
                        switch ($spawn) {

                            case "blue":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("blue-spawn", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Blue spawn point has been set!");
                                break;

                            case "red":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("red-spawn", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Red spawn point has been set!");
                                break;
                        }
                        return true;
                    }
                }
                if($this->plugin->admin[$username]["arena"][1] == "INF") {
                    if(isset($this->plugin->admin[$username]["spawn"])) {
                        $spawn = $this->plugin->admin[$username]["spawn"];
                        switch($spawn) {

                            case "zomb":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("z-spawn", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Zombie spawn point has been set!");
                                break;

                            case "surv":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("s-spawn", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Survivor spawn point has been set!");
                                break;
                        }
                        return true;
                    }
                    if(isset($this->plugin->admin[$username]["pos1"])) {
                        $pos = $this->plugin->admin[$username]["pos1"];
                        switch($pos) {

                            case "zomb":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("z-pos1", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Zombie position 1 point has been set!");
                                break;

                            case "surv":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("s-pos1", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Survivor position 1 point has been set!");
                                break;
                        }
                        return true;
                    }
                    if(isset($this->plugin->admin[$username]["pos2"])) {
                        $pos = $this->plugin->admin[$username]["pos2"];
                        switch($pos) {

                            case "zomb":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("z-pos2", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Zombie position 2 point has been set!");
                                break;

                            case "surv":
                                $event->setCancelled();
                                $data = ["level" => $level, "pos-x" => $x, "pos-y" => $y, "pos-z" => $z];
                                $cfg->set("s-pos2", $data);
                                $cfg->save();
                                unset($this->plugin->admin[$username]);
                                $player->sendMessage($this->plugin->prefix .  "Survivor position 2 point has been set!");
                                break;
                        }
                        return true;
                    }
                }
                // INSERT OTHER GAMEMODE HERE
            }
            return true;
        }
        return false;
    }
}