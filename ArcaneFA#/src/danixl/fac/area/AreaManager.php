<?php

declare(strict_types = 1);

namespace danixl\fac\area;

use danixl\fac\Main;

use pocketmine\entity\Entity;

use pocketmine\level\Position;
use pocketmine\math\Vector3;

use pocketmine\Player;

class AreaManager {

    private $plugin, $server;
	/** @var Area[] */
	public $areas = [];

	/** @var bool[] */
	public $selectingFirst = [];
	/** @var bool[] */
	public $selectingSecond = [];

	/** @var Vector3[] */
	public $firstPosition = [];
	/** @var Vector3[] */
	public $secondPosition = [];

	public function __construct(Main $plugin){
	    $this->plugin = $plugin;
	    $this->server = $plugin->getServer();
		if(!is_dir($this->plugin->getDataFolder())){
			mkdir($this->plugin->getDataFolder());
		}
		if(!file_exists($this->plugin->getDataFolder() . "areas.json")){
			file_put_contents($this->plugin->getDataFolder() . "areas.json", "[]");
		}
		$data = json_decode(file_get_contents($this->plugin->getDataFolder() . "areas.json"), true);
		foreach($data as $datum){
			new Area($datum["name"], $datum["flags"], new Vector3($datum["pos1"]["0"], $datum["pos1"]["1"], $datum["pos1"]["2"]), new Vector3($datum["pos2"]["0"], $datum["pos2"]["1"], $datum["pos2"]["2"]), $datum["level"], $datum["whitelist"], $this);
		}
	}

	public function saveAreas() : void{
		$areas = [];
		foreach($this->areas as $area){
			$areas[] = ["name" => $area->getName(), "flags" => $area->getFlags(), "pos1" => [$area->getFirstPosition()->getFloorX(), $area->getFirstPosition()->getFloorY(), $area->getFirstPosition()->getFloorZ()] , "pos2" => [$area->getSecondPosition()->getFloorX(), $area->getSecondPosition()->getFloorY(), $area->getSecondPosition()->getFloorZ()], "level" => $area->getLevelName(), "whitelist" => $area->getWhitelist()];
		}
		file_put_contents($this->plugin->getDataFolder() . "areas.json", json_encode($areas));
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canGetHurt(Entity $entity) : bool{
	    if($entity instanceof Player) {
            $username = strtolower($entity->getName());
            if(isset($this->plugin->edit[$username])) {
                return false;
            }
        }
		$o = true;
        foreach($this->areas as $area){
			if($area->contains(new Vector3($entity->getX(), $entity->getY(), $entity->getZ()), $entity->getLevel()->getName())){
				if(!$area->getFlag("damage")){ // flag is false. damage = false
					$o = false;
                }
				if($area->getFlag("damage")){ // true
					$o = true;
				}
			}
		}
		return $o;
	}

	/**
	 * @param Player   $player
	 * @param Position $position
	 *
	 * @return bool
	 */
	public function canEdit(Player $player, Position $position) : bool{
        $username = strtolower($player->getName());
        if(isset($this->plugin->edit[$username])) {
			return true;
		}
		$o = true;
		foreach($this->areas as $area){
			if($area->contains($position, $position->getLevel()->getName())){
				if(!$area->getFlag("edit")) { // false
					$o = false;
				}
				if($area->isWhitelisted(strtolower($player->getName()))){
					$o = true;
				}
			}
		}
		return $o;
	}

	/**
	 * @param Player   $player
	 * @param Position $position
	 *
	 * @return bool
	 */
	public function canTouch(Player $player, Position $position) : bool{
	    $username = strtolower($player->getName());
        if(isset($this->plugin->edit[$username])) {
			return true;
		}
		$o = true;
		foreach($this->areas as $area){
			if($area->contains(new Vector3($position->getX(), $position->getY(), $position->getZ()), $position->getLevel()->getName())){
				if(!$area->getFlag("touch")) { // flag is false
					$o = false;
				}
				if($area->isWhitelisted(strtolower($player->getName()))){
					$o = true;
				}
			}
		}
		return $o;
	}
}