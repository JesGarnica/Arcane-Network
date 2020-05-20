<?php

declare(strict_types=1);

namespace danixl\arcade\listeners;

use danixl\arcade\Arcade;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use pocketmine\Player;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerDeathEvent;

use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\tile\Sign;
use pocketmine\block\Block;

class BaseListener implements Listener {

    private $plugin, $server, $default, $lobby;

    private $blocked = [], $kills = [], $muted = [];

    public $playerTransferring = [];

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->default = $this->server->getDefaultLevel();
        $this->lobby = $this->default->getSafeSpawn();
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $this->plugin->getFTManager()->updateFloatingTextParticle($entity);
        }
    }

    /**
     * @param $event
     * @priority HIGH
     */

    public function onPreLogin(PlayerPreLoginEvent $event) {
        $query = $this->server->getQueryInformation();
        $cp = $query->getPlayerCount();
        $mp = $query->getMaxPlayerCount();
        $player = $event->getPlayer();
        if($cp == $mp) {
            $player->transfer("play.arcn.us", 19132, "Server is full. Transferring...");
            return;
        }
        $this->plugin->createPlayer($player);
    }

    /**
     * @param $event
     * @priority HIGH
     */

    public function onJoin(PlayerJoinEvent $event) {
        $event->setJoinMessage(false);
        $player = $event->getPlayer();
        $username = $player->getName();
        $this->plugin->getBossBar()->showTo($player);
        $this->plugin->getFTManager()->spawnFloatingTextParticleTo($player);
        $this->plugin->playerRegistered($username);
        $this->kills[strtolower($username)] = 0;
    }

    /**
     * @param $event
     * @priority HIGH
     */

    public function onQuit(PlayerQuitEvent $event) {
        $event->setQuitMessage(false);
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $player->teleport($this->lobby);

        if($this->plugin->getPlayer($player)) {
            $this->plugin->getPlayer($player)->purgeGameData();
            $this->plugin->destroyPlayer($player);
        }
        if(isset($this->blocked[$username])) unset($this->blocked[$username]);
        if(isset($this->muted[$username])) unset($this->muted[$username]);
        if(isset($this->kills[$username])) unset($this->kills[$username]);
        $this->plugin->getPerm()->delAttachment($player);
        $player->removeAllEffects();
        $this->plugin->getKit()->rmKitUsed($player);
    }

    /**
     * @param $event
     * @priority HIGH
     */

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $msg = $event->getMessage();

        if ($this->plugin->isPlayerStillOnTask("syncRanks", $player->getName())) {
            $event->setCancelled(true);
            $player->sendMessage($this->plugin->prefix .  "Account data loading...");
        }
        else {
            $tick = $this->server->getTick();
            $username = strtolower($player->getName());
            if (isset($this->muted[$username])) {
                $previous_tick = $this->muted[$username];
                $this->muted[$username] = $tick;
                if ($tick - $previous_tick < 80) {
                    $player->sendMessage($this->plugin->prefix .  "§8- §cLet other players chat! Keep the chat clean.");
                    $event->setCancelled();
                    return true;
                }
            } else {
                $this->muted[$username] = $tick;
            }
            $p = $this->plugin->getPlayer($player);
            $format = $p->getChatFormat() . " " . $msg;
            if ($p->hasId()) {
                $id = $p->getId();
                $game = $this->plugin->getManager()->getGame($id);
                $game->broadcastMSG($format);
                $event->setCancelled(true);
            } else {
                $event->setFormat($format);
            }
        }
    }

    /**
     * @param $event
     * @priority LOWEST
     * @return bool
     */

    public function onExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);

        if($p->hasId()) {
            $id = $p->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->getStatus() == 0) {
                    $event->setCancelled(true);
                    return false;
                }
            }
        }
        elseif(!$p->hasId()) {
            $event->setCancelled();
        }
    }

    /**
     * @param $event
     * @priority HIGH
     * @return bool
     */

    public function onDeath(PlayerDeathEvent $event) {
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();
        $event->setDeathMessage(false);

        $event->setDrops([]);

        if($cause instanceof EntityDamageByEntityEvent) {
            if($cause->getDamager() instanceof Player) {
                $player = $cause->getDamager();
                $username = $player->getName();
                $low_username = strtolower($username);

                if($this->plugin->isPlayerStillOnTask("syncCoins", $username) || $this->plugin->isPlayerStillOnTask("syncKD", $username) || $this->plugin->isPlayerStillOnTask("syncRanks", $username)) {
                    return false;
                }

                $p = $this->plugin->getPlayer($player);
                if(!$p->hasId()) {
                    return false;
                }

                $id = $p->getId();

                if($this->plugin->getManager()->gameExists($id)) {
                    $game = $this->plugin->getManager()->getGame($id);
                    if($game->getStatus() == 1) {
                        $p->addKills(1);
                        $this->kills[$low_username] = $this->kills[$low_username] + 1;
                        $kills = $this->kills[$low_username];

                        switch ($kills) {

                            case 2:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§1Double Kill§7! §r§8(§7" . $kills . "K§8)", 0);
                                break;

                            case 3:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§9Triple Kill§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 4:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§3Another Kill§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 5:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§bOver Kill§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 6:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§2Skilled Assassin§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 7:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§aSavage§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 8:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§eRampage§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 9:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§6Super Saiyan§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 10:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§cDominating§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case 11:
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§4Unstoppable§7! §r§8(§7" . $kills . "K§8)");
                                break;

                            case ($kills >= 12):
                                $game->broadcastMSG("§l§6» §7" . $username . " §r§o§7» §r§l§4Broke §athe §9Game§7! §r§8(§7" . $kills . "K§8)");
                                break;
                        }
                    }
                }
            }
            if($entity instanceof Player) {
                $username = $entity->getName();
                $low_username = strtolower($username);
                if($this->plugin->isPlayerStillOnTask("syncKD", $username)) {
                    return true;
                }

                $event->setKeepInventory(false);
                $event->setDrops([]);

                $kills = $this->kills[$low_username];

                if($kills == 0) {
                    $this->plugin->getPlayer($entity)->addDeaths(1);
                    unset($this->kills[$low_username]);
                    $this->kills[$low_username] = 0;
                } else {
                    $this->plugin->getPlayer($entity)->addDeaths(1);
                    unset($this->kills[$low_username]);
                    $this->kills[$low_username] = 0;
                    $entity->sendMessage($this->plugin->prefixDos .  "Your kill streak has been reset.");
                }
            }
            elseif($cause instanceof EntityDamageEvent) {
                $username = $entity->getName();
                $low_username = strtolower($username);

                $event->setKeepInventory(false);
                $event->setDrops([]);


                if($this->plugin->isPlayerStillOnTask("syncKD", $username)) {
                    return true;
                }
                $kills = $this->kills[$low_username];

                if($kills == 0) {
                    $this->plugin->getPlayer($entity)->addDeaths(1);
                    unset($this->kills[$low_username]);
                    $this->kills[$low_username] = 0;
                }
                else {
                    $this->plugin->getPlayer($entity)->addDeaths(1);
                    unset($this->kills[$low_username]);
                    $this->kills[$low_username] = 0;
                    $entity->sendMessage($this->plugin->prefixDos .  "Your kill streak has been reset.");
                }
            }
        }
    }

    public function onHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if(TextFormat::clean($item->getCustomName()) == "My Account") $this->plugin->createPlayerUI($player);
    }

    /**
     * @param $event
     * @priority HIGH
     * @return bool
     */

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());
        $action = $event->getAction();
        $item = $event->getItem();
        $block = $event->getBlock();

        if($action == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if(!isset($this->playerTransferring[$username])) {
                if($item->getId() == 345) {
                    $player->setImmobile();
                    $this->playerTransferring[$username] = true;
                    $this->plugin->getScheduler()->scheduleDelayedTask(
                        new class($this, $player, "play.arcn.us", 19132) extends Task {
                            private $plugin;
                            /** @var Player */
                            private $player;
                            /** @var string */
                            private $ip;
                            /** @var int */
                            private $port;

                            public function __construct($plugin, Player $player, string $ip, int $port){
                                $this->plugin = $plugin;
                                $this->player = $player;
                                $this->ip = $ip;
                                $this->port = $port;
                            }

                            public function onRun(int $currentTick){
                                $this->player->transfer($this->ip, $this->port, "Transferring...");
                                $username = strtolower($this->player->getName());
                                unset($this->plugin->playerTransferring[$username]);
                            }
                        },
                        1
                    );
                    $player->getInventory()->setHeldItemIndex(0);
                    return true;
                }
            }
        }

        if((
                $block->getId() == Block::SIGN_POST OR
                $block->getId() == Block::WALL_SIGN
            ) AND
            $action == PlayerInteractEvent::RIGHT_CLICK_BLOCK
        ) {
            $tile = $block->getLevel()->getTile($block);
            if($tile instanceof Sign) {
                $text = $tile->getText();
                $clean = TextFormat::clean($text[0]);
                switch($clean) {

                    case "CTF":
                            foreach($this->plugin->getManager()->signPositions as $k => $signData) {
                                $pos = $signData['pos'];
                                if($pos == $tile->asPosition()) {
                                    if($this->plugin->getManager()->gameExists($k)) {
                                        $game = $this->plugin->getManager()->getGame($k);
                                        if($game->arenaExists()) {
                                            if($this->plugin->getPlayer($player)->hasId()) {
                                                $player->sendMessage($this->plugin->prefix .  "You are currently in a match. Use /lobby to leave match.");
                                            }
                                            else {
                                                if($game->getStatus() == 1) {
                                                    if($player->hasPermission("arc.premiumjoin")) {
                                                        $game->forcePlayer($player);
                                                        $player->sendMessage($this->plugin->prefix .  "You have joined a busy match! Enjoy the match!");
                                                    }
                                                    else {
                                                        $player->sendMessage($this->plugin->prefixTres .  "Access Force Join feature with a rank.\n Purchase one at http://arcn.us");
                                                    }
                                                }
                                                else {
                                                    if($player->hasPermission("arc.premiumjoin")) {
                                                        $game->queuePlayer($player);
                                                    }
                                                    else {
                                                        $player->sendMessage($this->plugin->prefixTres .  "Selecting match of your choice is a premium feature.\nPurchase one at http://arcn.us");
                                                    }
                                                }
                                            }
                                        }
                                        else {
                                            $player->sendMessage($this->plugin->prefixDos .  "Arena data was not found.");
                                        }
                                    }
                                    else {
                                        $player->sendMessage($this->plugin->prefixDos .  "Game doesn't exist.");
                                    }
                                }
                            }
                            break;

                    case "INF":
                        foreach($this->plugin->getManager()->signPositions as $k => $signData) {
                            $pos = $signData['pos'];
                            if($pos == $tile->asPosition()) {
                                if($this->plugin->getManager()->gameExists($k)) {
                                    $game = $this->plugin->getManager()->getGame($k);
                                    if($game->arenaExists()) {
                                        if($this->plugin->getPlayer($player)->hasId()) {
                                            $player->sendMessage($this->plugin->prefix .  "You are currently in a match. Use /lobby to leave match.");
                                        }
                                        else {
                                            if($game->getStatus() == 1) {
                                                if($player->hasPermission("arc.premiumjoin")) {
                                                    $game->forcePlayer($player);
                                                    $player->sendMessage($this->plugin->prefix .  "You have joined a busy match! Enjoy the match!");
                                                }
                                                else {
                                                    $player->sendMessage($this->plugin->prefixTres .  "Access Force Join feature with a rank.\n Purchase one at http://arcn.us");
                                                }
                                            }
                                            else {
                                                if($player->hasPermission("arc.premiumjoin")) {
                                                    $game->queuePlayer($player);
                                                }
                                                else {
                                                    $player->sendMessage($this->plugin->prefixTres .  "Selecting match of your choice is a premium feature.\nPurchase one at http://arcn.us");
                                                }
                                            }
                                        }
                                    }
                                    else {
                                        $player->sendMessage($this->plugin->prefixDos .  "Arena data was not found.");
                                    }
                                }
                                else {
                                    $player->sendMessage($this->plugin->prefixDos .  "Game doesn't exist.");
                                }
                            }
                        }
                        break;

                    case "[Class]":
                        $kit = TextFormat::clean($text[1]);
                        if($this->plugin->getKit()->isKit($kit)) {
                            if($player->hasPermission("arc.kit." . strtolower($kit))) {
                                if($this->plugin->getKit()->kitUsed($player)) {
                                    if($this->plugin->getKit()->getKitUsed($player) == $kit) {
                                        $player->sendMessage($this->plugin->prefixDos .  "You already have selected this class.");
                                    }
                                    else {
                                        $this->plugin->getKit()->rmKitUsed($player);
                                        $this->plugin->getKit()->kitIsUsed($player, $kit);
                                        $player->sendMessage($this->plugin->prefix .  "You have selected the " . $kit . " class!");
                                    }
                                }
                                else {
                                    $this->plugin->getKit()->kitIsUsed($player, $kit);
                                    $player->sendMessage($this->plugin->prefix .  "You have selected the " . $kit . " class!");
                                }
                            }
                            else {
                                $player->sendMessage($this->plugin->prefixTres .  "Upgrade your rank at http://arcn.us to access this class.");
                            }
                        }
                        else {
                            $player->sendMessage($this->plugin->prefixDos .  "Class doesn't exist.");
                        }
                        break;
                }
            }
        }
    }
}