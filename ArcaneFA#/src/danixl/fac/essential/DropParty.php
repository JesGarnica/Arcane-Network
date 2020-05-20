<?php

namespace danixl\fac\essential;

use danixl\fac\Main;
use pocketmine\utils\Config;

use danixl\fac\task\DropPartyTask;
use danixl\fac\task\DropPartyDropTask;

class DropParty {

    private $m;

	private $dp, $dpTime, $dpStatus, $dpSecs;

	public function __construct(Main $m) {
		$this->m = $m;
	}

	public function loadDropParty() {
    	$this->dp = (new Config($this->m->getDataFolder() . "dropparty.yml", Config::YAML, array(
		"level" => "world",
		"mins" => 30,
		"duration" => 60,
		"message.started" => "DropParty has now started at /warp forest!",
		"message.ended" => "DropParty has now ended at Forest Hills",
		"message.countdown" => "DropParty will start in {mins} minutes at Forest Hills!",
		"popup.enabled" => true,
		"popup.message" => "DropParty dropping items at /warp forest!",
		"coords" => [
		"pos-x" => 0,
		"pos-y" => 0,
		"pos-z" => 0,
		],
		"items" => [
		57,
		42,
		22,
		41,
		],
		)))->getAll();
		$this->dpTime = $this->dp["mins"];
		$this->m->getScheduler()->scheduleRepeatingTask(new DropPartyTask($this->m), 20 * 60);
		$this->m->getScheduler()->scheduleRepeatingTask(new DropPartyDropTask($this->m), 20);
    }

    public function dropPartyCfg() {
    	return $this->dp;
    }

    public function getTime() {
    	return $this->dpTime;
    }

    public function setTime($time) {
    	$this->dpTime = $time;
    }

    public function getStatus() {
    	return $this->dpStatus;
    }

    public function setStatus($status) {
    	$this->dpStatus = $status;
    }

    public function getDropPartySecs() {
    	return $this->dpSecs;
    }

    public function setDropPartySecs($secs) {
    	$this->dpSecs = $secs;
    }
 
    public function getRandomItem() {
	  	$rand = mt_rand(0, count($this->dp["items"]) - 1);
	  	return $this->dp["items"][$rand];	
	}
}