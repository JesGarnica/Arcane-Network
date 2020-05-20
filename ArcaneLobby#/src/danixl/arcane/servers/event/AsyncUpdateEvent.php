<?php
namespace danixl\arcane\servers\event;

use danixl\arcane\Main;
use danixl\arcane\servers\ServerStats;
use pocketmine\event\plugin\PluginEvent;

class AsyncUpdateEvent extends PluginEvent{
	/** @var int */
	private $lastUpdate;
	/** @var int */
	private $currUpdate;
	/** @var ServerStats */
	private $sss;
	
	public static $handlerList = null;
	
	public function __construct(Main $sss, int $lastUpdate, int $currUpdate){
		parent::__construct($sss);
		$this->sss = $sss->getServerStats();
		$this->lastUpdate = $lastUpdate;
		$this->currUpdate = $currUpdate;
	}
	
	public function getSSS(): ServerStats{
		return $this->sss;
	}
	
	public function getCurrUpdate(): int{
		return $this->currUpdate;
	}
	
	public function getLastUpdate(): int{
		return $this->lastUpdate;
	}
}