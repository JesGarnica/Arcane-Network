<?php

declare(strict_types=1);

namespace danixl\arcade\task;

use pocketmine\item\Item;
use pocketmine\scheduler\Task;

use danixl\arcade\Arcade;
use pocketmine\utils\TextFormat;

class CheckPlayerTask extends Task {

    private $plugin, $server;

     public function __construct(Arcade $plugin) {
         $this->plugin = $plugin;
         $this->server = $this->plugin->getServer();
     }


     // MAKE IT MORE ROBUST SO OTHER MINI GAMES CAN BE ADDED

     public function onRun(int $currentTick): void {
         foreach($this->server->getOnlinePlayers() as $p) {
             if($p->getLevel()->getBlock($p->getPosition())->getId() == 90) {
                 $this->plugin->getManager()->findNextAvailableGame($p);
                 return;
             }
             if($p->y <= 1.5 && $p->isAlive()) {
                 $player = $this->plugin->getPlayer($p);
                 if($player->hasId()) {
                     $game = $this->plugin->getManager()->getGame($player->getId());
                     if($game->getStatus() == 0) {
                        $game->teleportQR($p);
                     }
                     $p->removeAllEffects();
                     $p->setHealth(20);
                     if($game->gameName() == "CTF") {
                         if($player->hasTeam()) {
                             $p->getInventory()->clearAll();
                             $msg = $p->getName() . " fell out of the world!";
                             $game->restoreOpposingTeamFlag($player->getTeam(), $p->getName(), $msg);
                             $game->teleportPlayer($p);
                         }
                         $this->plugin->sendUIItems($p, "CTF");
                     }
                 }
                 else $p->teleport($this->server->getDefaultLevel()->getSafeSpawn());
             }
         }
     }
}