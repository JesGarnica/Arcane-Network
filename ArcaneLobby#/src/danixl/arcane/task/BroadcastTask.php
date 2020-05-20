<?php

namespace danixl\arcane\task;

use danixl\arcane\Main;
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