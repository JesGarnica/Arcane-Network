<?php

namespace danixl\fac\task;

use danixl\fac\Main;

use pocketmine\scheduler\Task;

class CheckPlayerTask extends Task {

    private $plugin, $server;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
	}

	public function onRun(int $tick) {
		foreach($this->server->getOnlinePlayers() as $p) {
			if($p->y <= 3 && $p->isAlive()) {
				$p->teleport($this->server->getDefaultLevel()->getSpawnLocation());
			}
			if($p->getLevel()->getBlockAt($p->x, $p->y + 1, $p->z)->getId() == 90) {
                $this->plugin->getWarp()->teleportWarp($p, "forest");
                $p->setSpawn($this->plugin->getWarp()->getWarpPosition("forest"));
                $title = "~ §l§2Forest §aHills§r~";
                $subtitle = "Beware of the arrows!";
                $p->addTitle($title, $subtitle, $fadeIn = 20, $duration = 90, $fadeOut = 20);
			}
		}
	}
}