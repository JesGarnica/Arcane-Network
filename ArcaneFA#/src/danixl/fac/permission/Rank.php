<?php

namespace danixl\fac\permission;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\utils\Config;

class Rank {

    private $plugin, $server, $perm;

	private $ranks = [];
	 
	public function __construct(Main $plugin, PermissionManager $perm) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
		$this->perm = $perm;
		$this->loadRankPermissions();
		$this->loadFormatTags();
	}

	public function getPrimaryDefault() {
		$ranks = array_keys($this->ranks);
		foreach($ranks as $r) {
			if(isset($this->ranks[$r]["default"])) {
				return $r;
			}
		}
	}

	public function getRankClass($rank) {
	    if($rank == "unknown") {
	        return "secondary";
        }
		if(isset($this->ranks[$rank]["class"])) {
			return $this->ranks[$rank]["class"];
		}
	}

	public function isRank($rank) {
	    if($rank == "unknown") {
	        return true;
        }
		if(isset($this->ranks[$rank])) {
			return true;
		}
	}

	private function createRankPermissions() {
		$data = [
			"admin" => [
				"class" => "primary",
				"inherit" => ["mod"],
				"permissions" => ["arc.cmd.rank" => true, "pocketmine.command.ban" => true]
			], 
			"mod" => [
				"class" => "primary",
				"inherit" => ["member"],
				"permissions" => ["pocketmine.command.kick" => true]
			],
			"member" => [
				"class" => "primary",
				"permissions" => ["pocketmine.command.list" => true]
			],
			"guest" => [
				"class" => "primary",
				"default" => true,
				"permissions" => ["pocketmine.command.tell" => false, "arc.kit.knight" => true]
			],
			"vip" => [
				"class" => "secondary",
				"permissions" => ["pocketmine.command.list" => true]
			]
		];
		$rankConfig = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML);
		$rankConfig->set("ranks", $data);
		$rankConfig->save();
		$this->ranks = $data;
	}

	private function loadRankPermissions() {
		$ranks = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML);
		if(!$ranks->exists("ranks")) {
			$this->createRankPermissions();
		}
		else {
			$this->ranks = $ranks->get("ranks");
		}
	}

	private function getRankPermissions($rank) {
		if(isset($this->ranks[$rank]["permissions"])) {
			return $this->ranks[$rank]["permissions"];
		}
		return false;
	}

	private function getInheritedPermissions($rank) {
		if(isset($this->ranks[$rank]["inherit"])) {
			$inheritedRanks = $this->ranks[$rank]["inherit"];
			$addedPerms = [];
			foreach($inheritedRanks as $i) {
				if($this->isRank($i)) {
					$addedPerms = $this->getRankPermissions($i);
				}
			}
			if($addedPerms !== false) {
				return $addedPerms; 
			}
		}
		return false;
	}

	public function setRankPermissions(Player $player) {
		$username = strtolower($player->getName());
		$primary_rank = $this->plugin->getPlayer($player)->playerRank("primary");
		$secondary_rank = $this->plugin->getPlayer($player)->playerRank("secondary");
		$perm = $this->perm;
		$attachment = $perm->attachment[$username];

	/*
		$beta = ["leaf.ctf.kit.pigskin" => true, "leaf.ctf.kit.trooper" => true,
		"leaf.ctf.kit.sniper" => true, "leaf.ctf.kit.retriever" => true, "leaf.ctf.crate" => true];
		$alpha = ["leaf.ctf.kit.pigskin" => true, "leaf.ctf.kit.trooper" => true,
		"leaf.ctf.kit.sniper" => true, "leaf.ctf.kit.retriever" => true,
		"leaf.ctf.kit.hero" => true, "leaf.ctf.kit.bounty" => true, "leaf.ctf.crate" => true];

		$architect = ["leaf.ctf.edit" => true, "pocketmine.command.gamemode" => true,
		"leaf.ctf.warp" => true, "wea" => true, "pocketmine.command.time" => true];
		$youtube = ["leaf.ctf.kit.pigskin" => true, "leaf.ctf.kit.trooper" => true,
		"leaf.ctf.kit.sniper" => true, "leaf.ctf.kit.retriever" => true];
		$citizen = ["pocketmine.command.plugins" => true, "pocketmine.command.list" => true,
		"pocketmine.command.me" => false, "leaf.ctf.kit.pigskin" => true,
		"leaf.ctf.kit.trooper" => true];
		$mod = ["pocketmine.command.ban" => true, "pocketmine.command.kick" => true,
		"pocketmine.command.pardon" => true];
		$admin = ["pocketmine.command.teleport" => true, "pocketmine.command.kill" => true,
		"leaf.ctf.setcoins" => true, "leaf.ctf.warp" => true, "leaf.ctf.warp.manage" => true];
		$head_admin = ["pocketmine.command.gamemode" => true, "leaf.ctf.edit" => true,
		"leaf.ctf.manage" => true, "leaf.sendcoins" => true];
		$owner = ["pocketmine.command" => true, "leaf.ctf.sendcoins" => true,
		"leaf.ctf.setcoins" => true, "leaf.ctf.edit" => true, "leaf.ctf.manage" => true,
		"leaf.ctf.rank" => true, "leaf.ctf.warp.manage" => true, "leaf.ctf.warp" => true,
		"leaf.ctf.sign" => true, "leaf.ctf.getpos" => true, "leaf.ctf.kit" => true,
		"leaf.ctf.crate" => true];
	*/

		$attachment->clearPermissions();

		$inheritedPerms = $this->getInheritedPermissions($primary_rank);
		$basePerms = $this->getRankPermissions($primary_rank);
		if($inheritedPerms !== false) {
			$attachment->setPermissions($inheritedPerms);
		}
		$attachment->setPermissions($basePerms);


		if($secondary_rank !== "unknown") {
			$inheritedPerms = $this->getInheritedPermissions($secondary_rank);
			$basePerms = $this->getRankPermissions($secondary_rank);
			if($inheritedPerms !== false) {
				$attachment->setPermissions($inheritedPerms);
			}
			$attachment->setPermissions($basePerms);
		}
	}

	public function loadFormatTags() {
		if(!file_exists($this->plugin->getDataFolder() . "format.yml")) {
			$this->createFormatTags();
		}
	}
	
	public function createFormatTags() {
		$data = [
			"admin" => "[ADMIN]",
			"mod" => "[MOD]",
			"member" => "[MEMBER]",
			"vip" => "[VIP]"
		];
		$special_data = [
		    "faction" => "{%faction%}",
			"default_rank_prefix" => "○",
			"chat_message_prefix" => ":§7"
			
		];
		$formatConfig = new Config($this->plugin->getDataFolder() . "format.yml", Config::YAML);
		$formatConfig->set("tags", $data);
		$formatConfig->set("misc-tags", $special_data);
		$formatConfig->save();
	}

	public function getTag($rankTag) {
		$formatConfig = new Config($this->plugin->getDataFolder() . "format.yml", Config::YAML);
		if($formatConfig->exists("tags")) {
			$tags = $formatConfig->get("tags");
			if(isset($tags[$rankTag])) {
				return $tags[$rankTag];
			}
		}
		return "#not_found#";
	}

	public function getMiscTag($tag) {
		$formatConfig = new Config($this->plugin->getDataFolder() . "format.yml", Config::YAML);
		if($formatConfig->exists("misc-tags")) {
			$tags = $formatConfig->get("misc-tags");
			if(isset($tags[$tag])) {
				return $tags[$tag];
			}
		}
		return "#not_found";
	}

    public function createFormat(Player $player, $faction = false) {
        $username = $player->getName();
        $primary = $this->plugin->getPlayer($player)->playerRank("primary");
        $secondary = $this->plugin->getPlayer($player)->playerRank("secondary");
        $rank_p = $this->getTag($primary);
        $rank_s = $this->getTag($secondary);

        if($secondary == "unknown") {
            if($primary == $this->getPrimaryDefault()) {
                $format = $this->getMiscTag("default_rank_prefix") . " " . $this->getMiscTag("faction") . " " . $username . $this->getMiscTag("chat_message_prefix");
            }
            else {
                $string = "%rank% " . $this->getMiscTag("faction") . " " . $username . $this->getMiscTag("chat_message_prefix");
                $format = str_replace("%rank%", $rank_p, $string);
            }
        }
        else {
            if($primary == $this->getPrimaryDefault()) {
                $string = "%rank% " . $this->getMiscTag("faction") . " " . $username . $this->getMiscTag("chat_message_prefix");
                $format = str_replace("%rank%", $rank_s, $string);
            }
            else {
                $string = "%rank_s%" . "%rank_p% " . $this->getMiscTag("faction") . " " . $username . $this->getMiscTag("chat_message_prefix");
                $preformat = str_replace("%rank_s%", $rank_s, $string);
                $format = str_replace("%rank_p%", $rank_p, $preformat);
            }
        }
        if($faction == false || $faction == null) {
            $faction_format = str_replace($this->getMiscTag("faction"), " ", $format);
            $final_format = str_replace("  ", "", $faction_format);
        }
        else {
            $final_format = str_replace("%faction%", $faction, $format);
        }
        return $final_format;
    }
}