<?php

declare(strict_types=1);

namespace danixl\arcane\task;

use pocketmine\scheduler\Task;

use danixl\arcane\Main;

class CheckPlayerTask extends Task {

     private $plugin, $server;

     public function __construct(Main $plugin) {
         $this->plugin = $plugin;
         $this->server = $this->plugin->getServer();
     }

     public function onRun(int $currentTick): void {
         foreach($this->server->getOnlinePlayers() as $p) {
             if($p->y <= 3 && $p->isAlive()) {
                 $p->teleport($this->server->getDefaultLevel()->getSafeSpawn());
                 $p->setHealth(20);
             }
         }
     }
}