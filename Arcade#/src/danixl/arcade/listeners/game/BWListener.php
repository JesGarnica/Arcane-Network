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

class BWListener implements Listener {

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
        $this->plugin->sendUIItems($player, "BW");
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

    /*public function onDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);
        if($p->hasId()) {
            $id = $p->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                if($game->getStatus() == 1) {
                    $game->addRespawnCooldown($player);
                }
            }
        }
    }*/


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
                            return false;
                        }
                        $entity->getLevel()->addParticle(new DestroyBlockParticle($entity, Block::get(Block::REDSTONE_BLOCK)));
                        $entity->getLevel()->addSound(new AnvilFallSound($entity->getPosition()));
                        return true;
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

    /**
     * @param $event
     * @priority NORMAL
     * @return bool
     */

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);
        $username = strtolower($player->getName());

        if($p->hasId()) {
            $id = $p->getId();
            if($game = $this->plugin->getManager()->getGame($id)) {
                if($game->getStatus() == 1) {
                    $block = $event->getBlock();
                    if($block->getId() !== 35) {
                        $event->setCancelled();
                        return true;
                    }
                    return false;
                }
                $event->setCancelled();
                return false;
            }
        }
        elseif(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
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

        if($p->hasId()) {
            $id = $p->getId();
            if($game = $this->plugin->getManager()->getGame($id)) {
                if($game->getStatus() == 1) {
                    $block = $event->getBlock();
                    if($block->getId() !== 35) {
                        $event->setCancelled();
                        return true;
                    }
                    return false;
                }
                $event->setCancelled();
                return false;
            }
        }
        elseif(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
        }
        return false;
    }
}