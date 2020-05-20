<?php

namespace danixl\arcade\task;

use danixl\arcade\Arcade;

use pocketmine\scheduler\Task;

class BroadcastTask extends Task {

    private $plugin;

     public function __construct(Arcade $plugin) {
         $this->plugin = $plugin;
     }

     public function onRun(int $tick) {
         $this->plugin->broadcastMSG();
     }
}