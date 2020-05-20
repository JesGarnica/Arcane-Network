<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 10/7/18
 * Time: 2:56 PM
 */

namespace danixl\arcane\listeners;

use danixl\arcane\Main;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BaseListener implements Listener {

    private $plugin, $server;

    public $muted = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
    }

    public function onPreLogin(PlayerPreLoginEvent $event) {
        $player = $event->getPlayer();
        $this->plugin->createPlayer($player);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $username = $player->getName();
        $event->setJoinMessage("§8[§l§5*§r§8] §2" . $username . " §3is now online.");
        $this->plugin->playerRegistered($username);
        $this->plugin->getFTManager()->spawnFloatingTextParticleTo($player);
        $this->plugin->getBossBar()->showTo($player);
        $player->getInventory()->clearAll();
        $player->setHealth(20);
        $player->getInventory()->setHeldItemIndex(0);
        $ss = Item::get(345, 0, 1);
        $ss->setCustomName(TextFormat::GREEN . "Server Selector");
        $ps = Item::get(399, 0, 1);
        $ps->setCustomName(TextFormat::LIGHT_PURPLE . "My Account");
        $player->getInventory()->setItem(2, $ps);
        $player->getInventory()->setItem(3, $ss);

        $effect = Effect::getEffect(1);
        $effect = new EffectInstance($effect);
        $effect->setDuration(999999);
        $effect->setVisible(false);
        $effect->setAmplifier(1);
        $player->addEffect($effect);
        $effect = Effect::getEffect(8);
        $effect = new EffectInstance($effect);
        $effect->setDuration(999999);
        $effect->setVisible(false);
        $effect->setAmplifier(0);
        $player->addEffect($effect);
    }

    public function onQuit(PlayerQuitEvent $event) {
        $event->setQuitMessage(false);
        $player = $event->getPlayer();
        $player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
        if($this->plugin->getPlayer($player)) $this->plugin->destroyPlayer($player);
        if(isset($this->muted[strtolower($player->getName())])) unset($this->muted[strtolower($player->getName())]);
    }


    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $msg = $event->getMessage();

        if($this->plugin->isPlayerStillOnTask("syncRanks", $player->getName())) {
            $event->setCancelled(true);
            $player->sendMessage("Account data loading...");
        }
        else {
            $tick = $this->server->getTick();
            $username = strtolower($player->getName());
            if(isset($this->muted[$username])) {
                $previous_tick = $this->muted[$username];
                $this->muted[$username] = $tick;
                if($tick - $previous_tick < 80) {
                    $player->sendMessage("§8- §cLet other players chat! Keep the chat clean.");
                    $event->setCancelled();
                    return true;
                }
            }
            else {
                $this->muted[$username] = $tick;
            }
            $p = $this->plugin->getPlayer($player);
            $format = $p->getChatFormat() . " " .  $msg;
            $event->setFormat($format);
        }
    }

    public function onHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($item->getCustomName() == TextFormat::GREEN . "Server Selector") {
            $this->plugin->getServerStats()->getServerUI()->createMainUI($player);
        }
        if($item->getCustomName() == TextFormat::LIGHT_PURPLE . "My Account") {
            $this->plugin->createPlayerUI($player);
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $event->setCancelled();
    }

    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());

        if(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $username = strtolower($player->getName());

        if(!isset($this->plugin->edit[$username])) {
            $event->setCancelled();
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $event->setCancelled();
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        $entity = $event->getEntity();

        if($entity instanceof Player) {
            $this->plugin->getFTManager()->updateFloatingTextParticle($entity);
        }
    }
}