<?php

namespace danixl\fac\task;

use danixl\fac\Main;
use pocketmine\item\Item;

use pocketmine\scheduler\Task;

use pocketmine\math\Vector3;

class DropPartyDropTask extends Task {

    private $plugin, $server, $dp;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
		$this->dp = $this->plugin->getDropParty();
	}

	public function onRun(int $currentTick) {

		if($this->dp->getStatus() == "enabled") {
			$level = $this->server->getLevelByName($this->dp->dropPartyCfg()["level"]);
			foreach($this->server->getOnlinePlayers() as $p) {
				if($this->dp->dropPartyCfg()["popup.enabled"]) {
					$p->sendPopup($this->dp->dropPartyCfg()["popup.message"]);
				}
			}
			$this->dp->setDropPartySecs($this->dp->getDropPartySecs() + 1);

			if($level !== null) {
				if(count($level->getPlayers() < 4)) {
					$this->plugin->getLogger()->warning("Could not start DropParty. At least 4 people are needed.");
				}
				else {
					$level->dropItem(new Vector3($this->dp->dropPartyCfg()["coords"]["pos-x"], $this->dp->dropPartyCfg()["coords"]["pos-y"], $this->dp->dropPartyCfg()["coords"]["pos-z"]), Item::get($this->dp->getRandomItem(), 0, mt_rand(1, 5)));
				}
			} 
			else {
				$this->plugin->getLogger()->warning("§cItems could not be dropped. Lvl doesn't exist.");
			}
		}

		if($this->dp->getDropPartySecs() == $this->dp->dropPartyCfg()["duration"]) {			
			if($this->dp->getStatus() == "enabled") {
				$this->server->broadcastMessage($this->dp->dropPartyCfg()["message.ended"]);
				$this->dp->setStatus("ended");
				$this->dp->setDropPartySecs(0);				
				$this->dp->setTime($this->dp->dropPartyCfg()["mins"]);
			}
		}
	}
}
