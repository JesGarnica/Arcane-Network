<?php

namespace danixl\fac\task;

use danixl\fac\Main;

use pocketmine\Player;
use pocketmine\scheduler\Task;

class LogTask extends Task {

    private $plugin;

    public function __construct(Main $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun(int $tick) {
        $username = strtolower($this->player->getName());
        $this->player->sendMessage($this->plugin->prefix . "You can now leave the match!");
        $this->plugin->getPlayer($this->player)->delLog();
        $this->plugin->getScheduler()->cancelTask($this->plugin->logId[$username]);
        unset($this->plugin->logId[$username]);
    }
}
