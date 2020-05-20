<?php

namespace danixl\fac\permission;

use danixl\fac\Main;

use pocketmine\Player;

use pocketmine\utils\Config;

class RankOld {

	private $ranks = [];
	 
	public function __construct(Main $plugin, PermissionManager $perm) {
		$this->plugin = $plugin;
		$this->server = $this->plugin->getServer();
		$this->perm = $perm;
		$this->loadRankPermissions();
		$this->loadFormatBits();
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
		if(isset($this->ranks[$rank]["class"])) {
			return $this->ranks[$rank]["class"];
		}
	}

	public function isRank($rank) {
		if(isset($this->ranks[$rank])) {
			return true;
		}
	}

	private function createRankPermissions() {
		$data = [
			"admin" => [
				"class" => "primary",
				"inherit" => ["mod"],
				"permissions" => ["fac.unlimitedhomes" => true, "fac.command.warpmanage" => true, "fac.command.rank" => true, "pocketmine.command.ban" => true]
			], 
			"mod" => [
				"class" => "primary",
				"inherit" => ["member"],
				"permissions" => ["pocketmine.command.kick" => true, "fac.kit-sign" => true]
			],
			"member" => [
				"class" => "primary",
				"permissions" => ["pocketmine.command.list" => true]
			],
			"guest" => [
				"class" => "primary",
				"default" => true,
				"permissions" => ["pocketmine.command.tell" => false, "fac.command.home" => false, "fac.command.sethome" => false, "fac.command.sethome" => false, "fac.kit.knight" => true]
			],
			"vip" => [
				"class" => "secondary",
				"permissions" => ["fac.unlimitedhomes" => true]
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
		$beta = ["fac.ctf.kit.pigskin" => true, "fac.ctf.kit.trooper" => true,
		"fac.ctf.kit.sniper" => true, "fac.ctf.kit.retriever" => true, "fac.ctf.crate" => true];
		$alpha = ["fac.ctf.kit.pigskin" => true, "fac.ctf.kit.trooper" => true,
		"fac.ctf.kit.sniper" => true, "fac.ctf.kit.retriever" => true,
		"fac.ctf.kit.hero" => true, "fac.ctf.kit.bounty" => true, "fac.ctf.crate" => true];

		$architect = ["fac.ctf.edit" => true, "pocketmine.command.gamemode" => true,
		"fac.ctf.warp" => true, "wea" => true, "pocketmine.command.time" => true];
		$youtube = ["fac.ctf.kit.pigskin" => true, "fac.ctf.kit.trooper" => true,
		"fac.ctf.kit.sniper" => true, "fac.ctf.kit.retriever" => true];
		$citizen = ["pocketmine.command.plugins" => true, "pocketmine.command.list" => true,
		"pocketmine.command.me" => false, "fac.ctf.kit.pigskin" => true,
		"fac.ctf.kit.trooper" => true];
		$mod = ["pocketmine.command.ban" => true, "pocketmine.command.kick" => true,
		"pocketmine.command.pardon" => true];
		$admin = ["pocketmine.command.teleport" => true, "pocketmine.command.kill" => true,
		"fac.ctf.setcoins" => true, "fac.ctf.warp" => true, "fac.ctf.warp.manage" => true];
		$head_admin = ["pocketmine.command.gamemode" => true, "fac.ctf.edit" => true,
		"fac.ctf.manage" => true, "fac.sendcoins" => true];
		$owner = ["pocketmine.command" => true, "fac.ctf.sendcoins" => true,
		"fac.ctf.setcoins" => true, "fac.ctf.edit" => true, "fac.ctf.manage" => true,
		"fac.ctf.rank" => true, "fac.ctf.warp.manage" => true, "fac.ctf.warp" => true,
		"fac.ctf.sign" => true, "fac.ctf.getpos" => true, "fac.ctf.kit" => true,
		"fac.ctf.crate" => true];
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
            "ctf_team" => "#%team%",
            "team_placeholder" => "#",
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
		$rank_p = $this->getBit($primary);
		$rank_s = $this->getBit($secondary);

		if($secondary == "unknown") {
			if($primary == $this->getPrimaryDefault()) {
				$format = $this->getMiscBit("default_rank_prefix") . " " . $this->getMiscBit("faction") . " " . $username . $this->getMiscBit("chat_message_prefix");
			}
			else {
				$string = "%rank% " . $this->getMiscBit("faction") . " " . $username . $this->getMiscBit("chat_message_prefix");
				$format = str_replace("%rank%", $rank_p, $string);
			}
		}
		else {
			if($primary == $this->getPrimaryDefault()) {
				$string = "%rank% " . $this->getMiscBit("faction") . " " . $username . $this->getMiscBit("chat_message_prefix");
				$format = str_replace("%rank%", $rank_s, $string);
			}
			else {
				$string = "%rank_s%" . "%rank_p% " . $this->getMiscBit("faction") . " " . $username . $this->getMiscBit("chat_message_prefix");
				$preformat = str_replace("%rank_s%", $rank_s, $string);
				$format = str_replace("%rank_p%", $rank_p, $preformat);
			}
		}
		if($faction == false || $faction == null) {
			$faction_format = str_replace($this->getMiscBit("faction"), " ", $format);
			$final_format = str_replace("  ", "", $faction_format);
		}
		else {
			$final_format = str_replace("%faction%", $faction, $format);
		}
		return $final_format;
	}
}