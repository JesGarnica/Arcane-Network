<?php

namespace daniel\leaf\misc;

use daniel\leaf\Main;

class Purge {

	private $timeUnit;
	private $time = 15;
	private $taskId = false;
	private $purge = false;

	public function _construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	public function setTime($time) {
		$this->time = $mins;
	}

	public function getTime() {
		return $this->time;
	}

	public function getMinusTime(int $amount) {
		$minsBefore = $this->time;
		$this->time = $minsBefore - $amount;
		return $this->time;
	}

	public function getTaskId() {
		return $this->taskId;
	}

	public function setTaskId($taskId) {
		$this->taskId = $taskId;
	}

	public function getTaskTimeUnit() {
		return $this->timeUnit;
	}

	public function setTaskTimeUnit(int $unit) {
		$this->timeUnit = $unit;
	}

	public function setPurge() {
		$this->purge = true;
	}

	public function endPurge() {
		$this->purge = false;
	}

	public function isPurge() {
		if($this->purge) return true;
	}

	public function isSafe() {
		if(!$this->purge) return false;
	}

	public function getHudText() {

	}
}