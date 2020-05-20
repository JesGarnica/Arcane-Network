<?php

/* Since CTF was built intended to be a one gamemode per server it only support CTF running,
   not other mini games.
*/

declare(strict_types=1);

namespace danixl\arcade\listeners\game;

use danixl\arcade\Arcade;

use danixl\arcade\game\custom\ctf\RocketTask;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;

use pocketmine\item\Item;

use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\level\sound\AnvilFallSound;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\Player;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\block\Block;

use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\utils\TextFormat;

class CTFListener implements Listener {

    private $plugin, $server, $default, $lobby;

    private $ability = [];

    public $rocket = [];

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->default = $this->server->getDefaultLevel();
        $this->lobby = $this->default->getSafeSpawn();
        //$this->plugin->getScheduler()->scheduleRepeatingTask(new RocketTask($this), 20);
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
        $this->plugin->sendUIItems($player, "CTF");
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
        $player = $event->getPlayer();
        $p = $this->plugin->getPlayer($player);
        $player->removeAllEffects();

        if($p->getId()) {
            $id = $p->getId();
            if($this->plugin->getManager()->gameExists($id)) {
                $game = $this->plugin->getManager()->getGame($id);
                $u = $player->getName();
                $t = $p->getTeam();
                $msg = $u . " died.";
                $game->restoreOpposingTeamFlag($t, $u, $msg);
            }
        }
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
                                    if($tick - $previous_tick < 100) {
                                        $attacker->sendMessage($this->plugin->prefixDos .  "Wait 5 seconds until you can heal teammates.");
                                        return true;
                                    }
                                }
                                if($kit == "medic") {
                                    if($entity->getHealth() == 20) {
                                        $attacker->sendMessage($this->plugin->prefixDos .  $entity->getName() . " is already at max health.");
                                    }
                                    else {
                                        $attacker->sendMessage($this->plugin->prefix .  "You have healed your teammate!");
                                        $entity->sendMessage($this->plugin->prefix .  "You've received support from a Medic teammate!");
                                        $effect = Effect::getEffect(10);
                                        $effect = new EffectInstance($effect);
                                        $effect->setDuration(100);
                                        $effect->setVisible(true);
                                        $effect->setAmplifier(1);
                                        $entity->addEffect($effect);
                                        $this->ability[$username] = $tick;
                                    }
                                }
                                if($kit == "elf") {
                                    if($entity->getHealth() == 20) {
                                        $attacker->sendMessage($this->plugin->prefix .  $entity->getName() . " is already at max health.");
                                    }
                                    else {
                                        $entity->extinguish();
                                        $attacker->sendMessage($this->plugin->prefix .  "You have healed your teammate!");
                                        $entity->sendMessage($this->plugin->prefix .  "You've received support from an Elf teammate!");
                                        $effect = Effect::getEffect(10);
                                        $effect = new EffectInstance($effect);
                                        $effect->setDuration(140);
                                        $effect->setVisible(true);
                                        $effect->setAmplifier(1);
                                        $entity->addEffect($effect);
                                        $effect = Effect::getEffect(11);
                                        $effect = new EffectInstance($effect);
                                        $effect->setDuration(100);
                                        $effect->setVisible(true);
                                        $effect->setAmplifier(1);
                                        $entity->addEffect($effect);
                                        $this->ability[$username] = $tick;
                                    }
                                }
                                return true;
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

    public function onItemHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();

        /*switch($item->getName()) {
            case "Class Selector":
                $this->plugin->getKit()->createKitUI($player);
                break;
        }*/
        if(TextFormat::clean($item->getName()) == "Class Selector") {
            $this->plugin->getKit()->createKitUI($player);
        }
    }


    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $action = $event->getAction();

        if($action == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            switch(TextFormat::clean($item->getCustomName())) {

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

                case "Rocket Rod":
                    $p = $this->plugin->getPlayer($player);
                    if($p->getId()) {
                        $id = $p->getId();
                        if($this->plugin->getManager()->gameExists($id)) {
                            $game = $this->plugin->getManager()->getGame($id);
                            if($game->getStatus() == 1) {
                                $player->getLevel()->addParticle(new HugeExplodeParticle($player));
                                $player->getLevel()->addParticle(new SmokeParticle($player));
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

                                $this->rocket[strtolower($player->getName())] = 11;
                                $player->setXpLevel(0);
                                $player->setXpProgress(1);
                                $player->setAllowFlight(true);
                                $player->setAllowMovementCheats(true);
                                $player->setFlying(true);
                                $game->broadcastMSG("§l» §cA Rocket Stick has been ignited! Watch the skies!");
                                foreach($game->getPlayers() as $p => $s) {
                                    $player = $this->server->getPlayerExact($p);
                                    if($player instanceof Player) {
                                        $player->getLevel()->addSound(new BlazeShootSound($player, 2));
                                        $player->getLevel()->addSound(new FizzSound($player, 2));
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
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