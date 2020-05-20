<?php
/*          _         _                  __ __  ___  
           | |       | |                /_ /_ |/ _ \ 
  _ __ ___ | |__  ___| | _____           | || | | | |
 | '__/ _ \| '_ \/ __| |/ / _ \          | || | | | |
 | | | (_) | |_) \__ \   <  __/  ______  | || | |_| |
 |_|  \___/|_.__/|___/_|\_\___| |______| |_||_|\___/                      
*/
namespace danixl\arcane\servers;

use pocketmine\scheduler\Task;

class AsyncTaskCaller extends Task {

	/** @var ServerStats */
	private $sss;
	
	public function __construct(ServerStats $main){
		$this->sss = $main;
	}
	
	public function onRun(int $currentTick){
		if($this->sss->isAllowedToStartAsyncTask()){
			$this->sss->startAsyncTask($currentTick);
		}
	}
}
//Theory is when you know something, but it doesn"t work. Practice is when something works, but you don"t know why. Programmers combine theory and practice: Nothing works and they don"t know why!