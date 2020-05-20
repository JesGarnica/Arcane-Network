<?php

declare(strict_types=1);

namespace danixl\arcade\game\custom\ctf;

use danixl\arcade\Arcade;
use danixl\arcade\listeners\game\CTFListener;

use pocketmine\scheduler\Task;

use pocketmine\Player;

use pocketmine\level\particle\EntityFlameParticle;

use pocketmine\level\sound\FizzSound;

class RocketTask extends Task {

    private $plugin, $server, $listener, $rocket;

     public function __construct(CTFListener $listener) {
         $this->plugin = Arcade::getAPI();
         $this->listener = $listener;
         $this->server = $this->plugin->getServer();
     }
     
     public function onRun(int $tick) {
         
         if(count($this->listener->rocket) <= 1) {
             foreach($this->listener->rocket as $u => $t) {
                 $player = $this->server->getPlayerExact($u);
                 if($player instanceof Player) {
                     $p = $this->plugin->getPlayer($player);
                     if(!$p->hasId()) {
                         $player->setFlying(false);
                         $player->setAllowFlight(false);
                         $player->setXpProgress(0);
                         unset($this->listener->rocket[$u]);
                     }
                     else {
                         if($t > 1) {
                             unset($this->listener->rocket[$u]);
                             $this->listener->rocket[$u] = $t - 1;
                             if($this->listener->rocket[$u] <= 5) {
                                 $player->sendTip("Rocket fuel is low...");
                             }
                             $player->getLevel()->addParticle(new EntityFlameParticle($player));
                             $player->getLevel()->addSound(new FizzSound($player));
                             $meter = $player->getXpProgress();
                             if($meter > .10) {
                                 $newMeter = $meter - .10;
                                 $player->setXpProgress($newMeter);
                             }
                         }
                         else {
                             $player->setFlying(false);
                             $player->setAllowFlight(false);
                             $player->setXpProgress(0);
                             $player->sendTip("Rocket fuel is down...");
                             unset($this->listener->rocket[$u]);
                         }
                     }
                 }
             } 
         }
     }
}