<?php

declare(strict_types=1);

namespace danixl\arcade\game\custom\inf;

use danixl\arcade\Arcade;
use danixl\arcade\game\custom\inf\Infected;
use danixl\arcade\listeners\game\CTFListener;

use pocketmine\level\particle\RedstoneParticle;
use pocketmine\scheduler\Task;

use pocketmine\Player;

use pocketmine\level\particle\EntityFlameParticle;

use pocketmine\level\sound\FizzSound;

class InfectTask extends Task {

    private $plugin, $server, $game;

     public function __construct(Infected $game) {
         $this->plugin = Arcade::getAPI();
         $this->game = $game;
         $this->server = $this->plugin->getServer();
     }
     
     public function onRun(int $tick) {
         
         if(count($this->game->getAllPreInfected()) <= 1) {
             foreach($this->game->getAllPreInfected() as $u => $t) {
                 $player = $this->server->getPlayerExact($u);
                 if($player instanceof Player) {
                     $p = $this->plugin->getPlayer($player);
                     if(!$p->hasId()) {
                         $player->setXpProgress(0);
                         $this->game->rmPreInfection($u);
                     }
                     else {
                         if($t > 1) {
                             $this->game->increaseInfection($u);
                             if($this->game->getPreInfected($u) <= 5) {
                                 $player->sendPopup("§cTime's running out...turning into zombie...");
                             }
                             $player->getLevel()->addParticle(new RedstoneParticle($player));
                             $player->getLevel()->addSound(new FizzSound($player));
                             $meter = $player->getXpProgress();
                             if($meter > .10) {
                                 $newMeter = $meter - .10;
                                 $player->setXpProgress($newMeter);
                             }
                         }
                         else {
                             $player->setXpLevel(0);
                             $this->game->turnZombie($player);
                         }
                     }
                 }
             } 
         }
     }
}