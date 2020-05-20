<?php

namespace danixl\fac\task;

use danixl\fac\Main;

use pocketmine\scheduler\Task;

class KitManagerTask extends Task {

    private $plugin, $server;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
	}

	public function onRun(int $tick) {
		$players = $this->server->getOnlinePlayers();

		foreach($players as $p) {
			$player = $this->plugin->getPlayer($p);
			if($player->kitDataExists()) {
				$kit = $player->getAllKitsUsed();
				foreach($kit as $k) {
					$u = $player->getKitUnit($k);
					switch($u) {
						case 1:
						$player->rmKitUsed($k);
						$p->sendMessage($this->plugin->prefix . "Kit " . $k . " has now cooled.");
						break;

						case ($u > 1):
						$player->setKitUnit($k, $u - 1);
						break;
					}
				}
			}
		}
	}
}