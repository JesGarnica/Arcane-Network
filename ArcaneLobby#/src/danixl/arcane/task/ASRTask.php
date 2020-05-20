<?php

namespace danixl\arcane\task;

use danixl\arcane\Main;

use pocketmine\scheduler\Task;

class ASRTask extends Task {

    private $plugin, $server, $timeUnit;

	public function __construct(Main $plugin, $timeUnit) {
		$this->plugin = $plugin;
		$this->timeUnit = $timeUnit;
		$this->server = $this->plugin->getServer();
	}

	public function restartMins() {
		$minutes = $this->plugin->asrTime -= 1;
		
		switch($minutes) {
	   
		   case 1:
			   $this->server->broadcastMessage("§l» §cRestarting in " . $minutes . " minute.");
			   $this->plugin->getScheduler()->cancelTask($this->plugin->asrId);
			   unset($this->asrId);
			   $this->plugin->asrTime = 61;
			   $this->plugin->asrId = $this->plugin->getScheduler()->scheduleRepeatingTask(new ASRTask($this->plugin, 1), 20)->getTaskId();
			   break;
			   
		}
	}
	
	public function restartSecs() {
		$seconds = $this->plugin->asrTime -= 1;
		
		if($seconds == 1) {
			$this->server->broadcastMessage("§l» §cRestarting in " . $seconds . " second...");
			$this->server->shutdown();
			return true;
		}
		if($seconds == 10) {
			foreach($this->server->getOnlinePlayers() as $p) {
                $p->setImmobile(true);
                $p->kick("§l§6Server Restart. Join in 10 secs.");
			}
		}
		if($seconds <= 29) {
			$this->server->broadcastPopup("§l» §cRestarting in " . $seconds . " seconds.");
		}
	}
	
	public function onRun(int $tick) {
		switch($this->timeUnit) {
			
			case 0:
				$this->restartMins();
				break;
			
			case 1:
				$this->restartSecs();
				break;
				
			default:
				$this->server->getLogger()->warning("Invalid time method used for ASR Timer...");
		}
	}
}
