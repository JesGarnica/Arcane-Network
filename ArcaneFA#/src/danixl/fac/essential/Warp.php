<?php

namespace danixl\fac\essential;

use danixl\fac\Main;

use pocketmine\Player;

use pocketmine\level\Position;

use pocketmine\utils\Config;

class Warp  {

    private $m, $server;

    private $warpConfig, $warp;
	 
	public function __construct(Main $m) {
		$this->m = $m;
		$this->server = $this->m->getServer();
		$this->warpConfig = new Config($this->m->getDataFolder() . "warps.json", Config::JSON);
		$this->warp = $this->warpConfig->getAll();
	}

	public function createWarp(Player $player, $warpName) {
		$world = $player->getLevel()->getName();
		$x = $player->getX();
		$y = $player->getY();
		$z = $player->getZ() + 1;
		$data = [
			$world,
			$x,
			$y,
			$z
		];
		$this->warp[$warpName] = $data;
		$this->warpConfig->set($warpName, $data);
		$this->warpConfig->save();
	}

	public function delWarp($warpName) {
	    if(isset($this->warp[$warpName])) {
	        unset($this->warp[$warpName]);
	        $this->warpConfig->remove($warpName);
	        $this->warpConfig->save();
        }

	}

	public function warpExists($warpName) {
		if(isset($this->warp[$warpName])) {
		    return true;
        }
        return false;
	}

	public function teleportWarp(Player $player, $warpName) {
	    if(isset($this->warp[$warpName])) {
            $warp = $this->warp[$warpName];
            $level_name = $warp[0];
            $world = $this->server->getLevelByName($level_name);
            $isLoaded = $this->server->isLevelLoaded($level_name);
            $x = $warp[1];
            $y = $warp[2];
            $z = $warp[3];

            if(!$isLoaded) {
                $this->server->loadLevel($level_name);
            }
            $position = new Position($x, $y, $z, $world);
            $player->teleport($position);
        }
	}

    public function getWarpPosition($warpName) {
        if(isset($this->warp[$warpName])) {
            $warp = $this->warp[$warpName];
            $level_name = $warp[0];
            $world = $this->server->getLevelByName($level_name);
            $isLoaded = $this->server->isLevelLoaded($level_name);
            $x = $warp[1];
            $y = $warp[2];
            $z = $warp[3];

            if (!$isLoaded) {
                $this->server->loadLevel($level_name);
            }
            return new Position($x, $y, $z, $world);
        }
    }
}