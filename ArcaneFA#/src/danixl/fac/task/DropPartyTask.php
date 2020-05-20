<?php

namespace danixl\fac\task;

use danixl\fac\Main;
use pocketmine\scheduler\Task;

use pocketmine\math\Vector3;

class DropPartyTask extends Task {

    private $plugin, $server, $dp;
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
		$this->dp = $this->plugin->getDropParty();
	}

	public function onRun(int $currentTick) {
		$msg = str_replace("{mins}", $this->dp->getTime(), $this->dp->dropPartyCfg()["message.countdown"]);
		if($this->dp->getTime() > 0) {
			$this->server->broadcastMessage($msg);
		}

		if($this->dp->getTime() == 0) {
			$this->server->broadcastMessage($this->dp->dropPartyCfg()["message.started"]);
			$this->dp->setStatus("enabled");		
		}
		$this->dp->setTime($this->dp->getTime() - 1);
	}
}
