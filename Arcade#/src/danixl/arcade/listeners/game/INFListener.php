<?php

declare(strict_types=1);

namespace danixl\arcade\listeners\game;

use danixl\arcade\Arcade;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\item\Item;

use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\sound\AnvilFallSound;

use pocketmine\Player;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\block\Block;

use pocketmine\level\particle\DestroyBlockParticle;

class INFListener implements Listener {

    private $plugin, $server, $default, $lobby;

    public $ability = [];

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->default = $this->server->getDefaultLevel();
        $this->lobby = $this->default->getSafeSpawn();
    }

    /**
     * @param $event
     * @priority NORMAL
     */

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $player->setHealth(20);
        $player->setFood(20);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $this->plugin->sendUIItems($player, "INF");
    }

    /**
     * @param $event
     * @priority LOWEST
     */

    public function onDrop(PlayerDropItemEvent $event) {
        $event->setCancelled();
    }

    /**
     * @param $event
     * @priority NORMAL
     */

    public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);

        if($p->hasId()) {
            $id = $p->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->getStatus() == 1) {
                    $game->onPlayerRespawnEvent($player);
                }
            }
        }
    }

    /**
     * @param $event
     * @priority NORMAL
     * @return bool
     */

    public function onDeath(PlayerDeathEvent $event) {
        $event->setDrops([]);
    }


    public function Damage(EntityDamageEvent $event) {
        $entity = $event->getEntity();

        if($entity instanceof Player) {
            $player = $this->plugin->getPlayer($entity);
            if(!$player->hasId()) {
                $event->setCancelled();
                return false;
            }
        }

        if($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();
            if($entity instanceof Player && $attacker instanceof Player) {
                $p = $this->plugin->getPlayer($entity);
                $ap = $this->plugin->getPlayer($attacker);
                if($p->hasId() && $ap->hasId()) {
                    $id = $p->getId();
                    $attackId = $ap->getId();
                    if($id == $attackId) {
                        $team = $p->getTeam();
                        $attackTeam = $ap->getTeam();
                        if(!$team || !$attackTeam) {
                            $event->setCancelled();
                            return false;
                        }
                        if($attackTeam == $team) {
                            $event->setCancelled();
                            if($this->plugin->getKit()->kitUsed($attacker)) {
                                $kit = strtolower($this->plugin->getKit()->getKitUsed($attacker));
                                $tick = $this->server->getTick();
                                $username = strtolower($attacker->getName());
                                if(isset($this->ability[$username])) {
                                    $previous_tick = $this->ability[$username];
                                    unset($this->ability[$username]);
                                    $this->ability[$username] = $tick;
                                    if ($tick - $previous_tick < 100) {
                                        $attacker->sendMessage($this->plugin->prefixDos .  "Wait 5 seconds until you can cure teammates.");
                                        return true;
                                    }
                                }
                                if($kit == "medic") {
                                    if($this->plugin->getManager()->gameExists($attackId)) {
                                        $victim = strtolower($entity->getName());
                                        $game = $this->plugin->getManager()->getGame($attackId);
                                        if ($game->preInfected($victim)) {
                                            $attacker->sendMessage($this->plugin->prefixDos .  $entity->getName() . " is currently not infected.");
                                        }
                                        else {
                                            $attacker->sendMessage($this->plugin->prefix .  "You have cured your teammate!");
                                            $entity->sendMessage($this->plugin->prefix .  "You have been cured by a Medic!");
                                            $game->rmPreInfection($victim);
                                            $this->ability[$username] = $tick;
                                        }
                                    }
                                }
                            }
                            return true;
                        }
                        if($attackTeam == "zombie") {
                            if($this->plugin->getManager()->gameExists($attackId)) {
                                $game = $this->plugin->getManager()->getGame($attackId);
                                $victim = strtolower($entity->getName());
                                if($game->preInfected($victim)) {
                                    $attacker->sendMessage($this->plugin->prefixDos .  "You have already infected this player.\nThey'll become a zombie in 8 seconds.");
                                }
                                else {
                                    $game->startInfection($victim);
                                    $entity->setXpLevel(0);
                                    $entity->setXpProgress(1);
                                    $attacker->sendMessage($this->plugin->prefix .  "You have infected " . $entity->getName() . "!");
                                    $entity->sendMessage($this->plugin->prefix .  "You have become infected. Find a medic survivor to cure you!");
                                }
                            }
                        }
                        $entity->getLevel()->addParticle(new DestroyBlockParticle($entity, Block::get(Block::REDSTONE_BLOCK)));
                        $entity->getLevel()->addSound(new AnvilFallSound($entity->getPosition()));
                        return false;
                    }
                }
            }
            if($attacker instanceof Player) {
                $player = $this->plugin->getPlayer($attacker);
                if(!$player->hasId()) {
                    $event->setCancelled();
                }
            }
        }
    }

    /*public function onHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($item->getCustomName() == TextFormat::GREEN . "Kit Selector") {
            $this->plugin->getKit()->createKitUI($player);
        }
    }*/

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        switch($item->getCustomName()) {

            case "Smoke Bomb":
                $player->getLevel()->addParticle(new HugeExplodeParticle($player));
                $effect = Effect::getEffect(14);
                $effect = new EffectInstance($effect);
                $effect->setDuration(200);
                $effect->setVisible(true);
                $effect->setAmplifier(1);
                $player->addEffect($effect);
                $effect = Effect::getEffect(1);
                $effect = new EffectInstance($effect);
                $effect->setDuration(200);
                $effect->setVisible(true);
                $effect->setAmplifier(1);
                $player->addEffect($effect);
                $player->sendMessage($this->plugin->prefix .  "Smoke Bomb activated. Hurry and take advantage!");
                $count = $item->getCount();
                if($count > 1) {
                    $count = $count - 1;
                    $name = $item->getCustomName();
                    $player->getInventory()->removeItem($item);
                    $item = Item::get($item->getId(), 0, $count);
                    $item->setCustomName($name);
                    $player->getInventory()->setItemInHand($item);
                }
                else {
                    $player->getInventory()->removeItem($item);
                }
                break;

            case "Super Killer":
                $player->getLevel()->addParticle(new RedstoneParticle($player));
                $effect = Effect::getEffect(5);
                $effect = new EffectInstance($effect);
                $effect->setDuration(200);
                $effect->setVisible(true);
                $effect->setAmplifier(1);
                $player->addEffect($effect);
                $player->sendMessage($this->plugin->prefix .  "Hurry and start attacking! Super Killer activated.");
                $count = $item->getCount();
                if($count > 1) {
                    $count = $count - 1;
                    $name = $item->getCustomName();
                    $player->getInventory()->removeItem($item);
                    $item = Item::get($item->getId(), 0, $count);
                    $item->setCustomName($name);
                    $player->getInventory()->setItemInHand($item);
                }
                else {
                    $player->getInventory()->removeItem($item);
                }
                break;

            case "Speed Boost":
                $player->getLevel()->addParticle(new SplashParticle($player));
                $effect = Effect::getEffect(1);
                $effect = new EffectInstance($effect);
                $effect->setDuration(200);
                $effect->setVisible(true);
                $effect->setAmplifier(1);
                $player->addEffect($effect);
                $player->sendMessage($this->plugin->prefix .  "Speed Boost activated. RUN!");
                $count = $item->getCount();
                if($count > 1) {
                    $count = $count - 1;
                    $name = $item->getCustomName();
                    $player->getInventory()->removeItem($item);
                    $item = Item::get($item->getId(), 0, $count);
                    $item->setCustomName($name);
                    $player->getInventory()->setItemInHand($item);
                }
                else {
                    $player->getInventory()->removeItem($item);
                }
                break;
        }
    }

    /**
     * @param $event
     * @priority NORMAL
     * @return bool
     */

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());

        if(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
        }
        $p = $this->plugin->getPlayer($player);

        if($p->hasId()) {
            $id = $p->getId();
            if($game = $this->plugin->getManager()->getGame($id)) {
                $game->onBlockBreakEvent($player, $event->getBlock());
                return true;
            }
        }
        return false;
    }

    /**
     * @param $event
     * @priority NORMAL
     * @return bool
     */

    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);
        $username = strtolower($player->getName());

        if(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
        }

        if($p->hasId()) {
            $id = $p->getId();
            if ($game = $this->plugin->getManager()->getGame($id)) {
                $game->onBlockPlaceEvent($player, $event->getBlockReplaced());
                return true;
            }
        }
        return false;
    }
}