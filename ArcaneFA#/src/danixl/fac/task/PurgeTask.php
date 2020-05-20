<?php

namespace daniel\leaf\task;

use pocketmine\scheduler\PluginTask;

use daniel\leaf\Main;

use daniel\leaf\Purge;

class PurgeTask extends PluginTask {

	public function __construct(Main $plugin, Purge $purge, $timeUnit) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
		$this->purge = $purge;
		$this->timeUnit = $timeUnit;
		$this->server = $this->plugin->getServer();
	}

	public function minutes() {
		$minutes = $this->purge->getMinusTime(1);

		if($minutes == 1) {
			if($this->purge->isPurge()) {
				$this->server->getScheduler()->cancelTask($this->purge->getTaskId());
				$this->purge->setTaskId(false);
				$this->purge->setTime(61);
				$this->purge->setTaskTimeUnit(1);
				$this->purge->setTaskId($this->server->getScheduler()->scheduleRepeatingTask(new PurgeTask($this->plugin, $this->purge, 1), 20)->getTaskId());
			}
			elseif($this->isSafe()) {
				$this->server->getScheduler()->cancelTask($this->purge->getTaskId());
				$this->purge->setTaskId(false);
				$this->purge->setTime(61);
				$this->purge->setTaskTimeUnit(1);
				$this->purge->setTaskId($this->server->getScheduler()->scheduleRepeatingTask(new PurgeTask($this->plugin, $this->purge, 1), 20)->getTaskId());
			}
		}
	}
	
	public function seconds() {
		$seconds = $this->purge->getMinusTime(1);

		if($seconds == 1) {
			if($this->purge->isPurge()) {
				$this->server->getScheduler()->cancelTask($this->purge->getTaskId());
				$this->purge->setTaskId(false);
				$this->purge->setTime(16);
				$this->purge->endPurge();
				$this->purge->setTaskTimeUnit(0);
				$this->purge->setTaskId($this->server->getScheduler()->scheduleRepeatingTask(new PurgeTask($this->plugin, $this->purge, 0), 1200)->getTaskId());
				$title = "§l§aPurge Over§f!";
				$subtitle = "Recover and rest...";
			}
			elseif($this->isSafe()) {
				$this->server->getScheduler()->cancelTask($this->purge->getTaskId());
				$this->purge->setTaskId(false);
				$this->purge->setTime(5);
				$this->purge->setPurge();
				$this->purge->setTaskTimeUnit(0);
				$this->purge->setTaskId($this->server->getScheduler()->scheduleRepeatingTask(new PurgeTask($this->plugin, $this->purge, 0), 1200)->getTaskId());
				$title = "§l§4P§cu§4r§cg§4e §6Commenced§f!";
				$subtitle = "Raid, destroy or defend turfs!";
			}
			foreach($this->plugin->getOnlinePlayers() as $p) {
				$p->addTitle($title, $subtitle, 20, 100, 20);
			}
		}
	}
	
	public function onRun(int $tick) {
		switch($this->timeUnit) {
			
			case 0:
				$this->minutes();
				break;
			
			case 1:
				$this->seconds();
				break;
				
			default:
				$this->server->getLogger()->warning("Invalid time unit used for Purge Timer...");
		}
	}
}
