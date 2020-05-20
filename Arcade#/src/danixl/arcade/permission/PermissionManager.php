<?php

namespace danixl\arcade\permission;

use danixl\arcade\Arcade;

use pocketmine\Player;

class PermissionManager {

	public $attachment = [];

	private $plugin, $server;

	public function __construct(Arcade $plugin) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
	}

	public function setAttachment(Player $player) {
		$username = strtolower($player->getName());

		if(!$this->hasAttachment($player)) {
			$this->attachment[$username] = $player->addAttachment($this->plugin);
		}
	}

	public function delAttachment(Player $player) {
		$username = strtolower($player->getName());

		if($this->hasAttachment($player)) {
			$attachment = $this->attachment[$username];
			unset($this->attachment[$username]);
			$player->removeAttachment($attachment);
		}
	}

	public function hasAttachment(Player $player) {
		$username = strtolower($player->getName());

		if(isset($this->attachment[$username])) {
			return true;
		}
		else {
			return false;
		}
	}
}