<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 3/1/19
 * Time: 2:57 PM
 */

namespace danixl\fac\listener;


use danixl\fac\Main;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\event\Listener;

use pocketmine\Player;

class AreaListener implements  Listener {
    
    private $plugin, $server;
    
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->server = $plugin->getServer();
    }

    public function onBlockTouch(PlayerInteractEvent $event) : void{
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if(!$this->plugin->getArea()->canTouch($player, $block)){
            $event->setCancelled();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) : void{
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $playerName = strtolower($player->getName());
        if(isset($this->plugin->getArea()->selectingFirst[$playerName])){
            unset($this->plugin->getArea()->selectingFirst[$playerName]);

            $this->plugin->getArea()->firstPosition[$playerName] = $block->asVector3();
            $player->sendMessage($this->plugin->prefix . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
            $event->setCancelled();
        }
        elseif(isset($this->plugin->getArea()->selectingSecond[$playerName])){
            unset($this->plugin->getArea()->selectingSecond[$playerName]);

            $this->plugin->getArea()->secondPosition[$playerName] = $block->asVector3();
            $player->sendMessage($this->plugin->prefix . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
            $event->setCancelled();
        }
        else{
            if(!$this->plugin->getArea()->canEdit($player, $block)){
                $event->setCancelled();
            }
        }
    }


    /**
     * @param BlockBreakEvent $event
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event) : void{
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $playerName = strtolower($player->getName());
        if(isset($this->plugin->getArea()->selectingFirst[$playerName])){
            unset($this->plugin->getArea()->selectingFirst[$playerName]);

            $this->plugin->getArea()->firstPosition[$playerName] = $block->asVector3();
            $player->sendMessage($this->plugin->prefix . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
            $event->setCancelled();
        }

        elseif(isset($this->plugin->getArea()->selectingSecond[$playerName])){
            unset($this->plugin->getArea()->selectingSecond[$playerName]);

            $this->plugin->getArea()->secondPosition[$playerName] = $block->asVector3();
            $player->sendMessage($this->plugin->prefix . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
            $event->setCancelled();
        }
        else{
            if(!$this->plugin->getArea()->canEdit($player, $block)){
                $event->setCancelled();
            }
        }
    }
}