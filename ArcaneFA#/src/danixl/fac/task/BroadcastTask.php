<?php

namespace danixl\fac\task;

use danixl\fac\Main;
use pocketmine\scheduler\Task;

class BroadcastTask extends Task {

    private $plugin;

     public function __construct(Main $plugin) {
         $this->plugin = $plugin;
     }

     public function onRun(int $tick) {
         $this->plugin->broadcastMSG();
     }
}