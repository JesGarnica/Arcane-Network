<?php
/*          _         _                  __ __  ___  
           | |       | |                /_ /_ |/ _ \ 
  _ __ ___ | |__  ___| | _____           | || | | | |
 | '__/ _ \| '_ \/ __| |/ / _ \          | || | | | |
 | | | (_) | |_) \__ \   <  __/  ______  | || | |_| |
 |_|  \___/|_.__/|___/_|\_\___| |______| |_||_|\___/                      
*/
namespace danixl\arcane\servers;

use danixl\arcane\Main;

use pocketmine\Server;

use pocketmine\math\Vector3;

use pocketmine\Player;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use danixl\arcane\servers\event\AsyncUpdateEvent;

/*
 * ServerStats is a very stripped down version of SignServerStats, but all the core server components are kept.
 * Now compatible with forms and NPC.
*/
class ServerStats {
	/** @var Config */
	private $cfg;

	private $serverUI;
	
	/** @var Config */
	private $db;

	/** @var Server */
	private $server;

	/** @var float */
	private $timeout;
	/** @var array */
	public $cacheServers = [];
	/** @var array */
	private $doCheckServers = [];
	/** @var bool */
	private $debug = false;
	/** @var bool */
	private $asyncTaskIsRunning = false;
	/** @var int */
	private $lastRefreshTick = -1;
	/** @var array */
	private $doRefreshSigns = [];
	/** @var array */
	private $asyncTaskFullData = [];
	/** @var array */
	private $asyncTaskMODTs = [];
	/** @var array */
	private $asyncTaskPlayers = [];
	/** @var array */
	private $asyncTaskIsOnline = [];
	
	public function __construct(Main $plugin){
	    $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
		@mkdir($this->plugin->getDataFolder());
		$this->db = new Config($this->plugin->getDataFolder()."servers.yml", Config::YAML);
		$this->cfg = new Config($this->plugin->getDataFolder()."config.yml", Config::YAML);
		if($this->cfg->get("ConfigVersion") != 3){
			$this->cfg->set('async-task-call-ticks', 200);
			$this->cfg->set('always-start-async-task', false);
			$this->cfg->set('server-query-timeout-sec', 2.5);
			$this->cfg->set('debug', false);
			$this->cfg->set('ConfigVersion', 3);
		}
		$this->cfg->save();
		if($this->cfg->get('debug')){
			$this->debug = true;

		}
		$this->cacheServers = $this->db->getAll();
		$this->loadServers();
		$this->serverUI = new ServerUI($plugin);
		$this->timeout = $this->cfg->get('server-query-timeout-sec');
		$this->plugin->getScheduler()->scheduleRepeatingTask(
			new AsyncTaskCaller($this), $this->cfg->get("async-task-call-ticks")
		);
	}

	public function getServerUI(): ServerUI {
	    return $this->serverUI;
    }

	/**
	 * Returns the Tick in which the oldest data was refreshed.
	 *
	 * @return int
	 */
	public function getLastRefreshTick(): int{
		return $this->lastRefreshTick;
	}
	
	/**
	 * @return array [string $serverID => bool $isOnline]
	 */
	public function getServerOnline(): array{
		return $this->asyncTaskIsOnline;
	}
	
	/**
	 * Returns the full queryResponse
	 *
	 * @return array [string $serverID => array $queryResponse]
	 */
	public function getFullData(): array{
		return $this->asyncTaskFullData;
	}
	
	/**
	 * @return array [string $serverID => string $modt]
	 */
	public function getMODTs(): array{
		return $this->asyncTaskMODTs;
	}
	
	/**
	 * @return array [string $serverID => [int $numplayers, int $maxplayers]]
	 */
	public function getPlayerData(): array{
		return $this->asyncTaskPlayers;
	}
	
	/**
     * @param string  $category
     * @param string  $name
	 * @param string  $ip
	 * @param int     $port
	 */
	public function addServerToConfig(string $category, string $name, string $ip, int $port){
	    $data = [$name => [$ip, $port]];
	    if(empty($this->cacheServers)) {
            $this->cacheServers[$category][$name] = [$ip, $port];
            $this->db->set($category, $data);
        }
        else {
            $this->cacheServers[$category][$name] = [$ip, $port];
            $this->db->setAll($this->cacheServers);
        }
		$this->db->save(true);
		var_dump($this->cacheServers);
	}
	
	/**
	 * @param string $ip
	 * @param int $port
	 *
	 * @return bool Success (if false is returned server is already added)
	 */
	public function addServer(string $ip, int $port): bool{
		if(isset($this->doCheckServers[$ip."@".$port])){
			return false;
		}
		$this->doCheckServers[$ip."@".$port] = [$ip, $port];
		return true;
	}
	
	/**
	 * @param string $ip
	 * @param int $port
	 *
	 * @return bool Success
	 */
	public function removeServer(string $ip, int $port): bool{
		if(isset($this->doCheckServers[$ip."@".$port])){
			unset($this->doCheckServers[$ip."@".$port]);
			return true;
		}
		return false;
	}

	public function loadServers() {
	    $db = $this->cacheServers;
	    foreach($db as $category) {
	        foreach($category as $name) {
	            $ip = $name[0];
	            $port = $name[1];
                $this->addServer($ip, $port);
            }
        }
    }

    public function getPlayerSlots(string $serverAddress): string {
	    if(isset($this->asyncTaskIsOnline[$serverAddress])) {
	        $isOnline = $this->asyncTaskIsOnline[$serverAddress];
	        if($isOnline) {
                if(isset($this->asyncTaskPlayers[$serverAddress])) {
                    $currPlayers = $this->asyncTaskPlayers[$serverAddress][0];
                    $maxPlayers = $this->asyncTaskPlayers[$serverAddress][1];
                    $format = "§8[§a" . $currPlayers . "§7/§a" . $maxPlayers . "§8]";
                    return $format;
                }
                else {
                    $format = "[0/0]";
                    return $format;
                }
            }
            $format = "§8[§cOffline§8]";
	        return $format;
        }
        $format = "§8[§cOffline§8]";
        return $format;
    }
	
	/**
	 * @internal
	 *
	 * @return bool
	 */
	public function debugEnabled(): bool{
		return $this->debug;
	}
	
	/**
	 * @internal
	 *
	 * @param $currTick
	 */
	public function startAsyncTask($currTick){
		$this->asyncTaskIsRunning = true;
		$this->server->getAsyncPool()->submitTask(new ServersAsyncTask($this->doCheckServers, $this->debug, $this->timeout, $currTick));
	}
	
	/**
	 * @internal
	 *
	 * @param $data
	 * @param $scheduleTime
	 */
	public function asyncTaskCallBack($data, $scheduleTime){
		$this->asyncTaskIsRunning = false;
		if($this->debug){
			$this->plugin->getLogger()->debug("AsyncTaskResponse:");
		}
		$this->asyncTaskMODTs = [];
		$this->asyncTaskPlayers = [];
		$this->asyncTaskIsOnline = [];
		if(empty($data)){
			return;
		}
		foreach($data as $serverID => $serverData){
			$this->asyncTaskIsOnline[$serverID] = $serverData[0];
			if($serverData[0]){
				$this->asyncTaskMODTs[$serverID] = $serverData[2];
				$this->asyncTaskPlayers[$serverID] = $serverData[1];
				$this->asyncTaskFullData[$serverID] = $serverData[3];
			}
		}
		$this->server->getPluginManager()->callEvent(new AsyncUpdateEvent($this->plugin, $this->lastRefreshTick, $scheduleTime));
		$this->lastRefreshTick = $scheduleTime;
		
		$currTick = $this->server->getTick();
		if($currTick - $scheduleTime >= $this->cfg->get('AsyncTaskCall')){
			$this->startAsyncTask($currTick);
		}
	}
	
	/**
	 * @internal
	 *
	 * @return bool
	 */
	public function isAllowedToStartAsyncTask(): bool{
		return $this->cfg->get('always-start-async-task') ? true : !$this->asyncTaskIsRunning;
	}
}