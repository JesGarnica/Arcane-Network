<?php

declare(strict_types=1);


namespace danixl\arcade\game;

use danixl\arcade\Arcade;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\EndermanTeleportSound;


use pocketmine\utils\Config;

use pocketmine\level\Position;

use pocketmine\Player;

use pocketmine\block\Block;

abstract class Game {

    // Make the Game class more flexible

	protected $player = [];
	protected $queue = [];
	protected $pos = [];

	protected $qrRoom;

	protected $status = 0, $count = 0;
	protected $team = [];
	protected $teamEnabled = false, $scoreEnabled = true;

	protected $time;
	protected $taskId;

	protected $gameName = 'GAME';

	public function __construct(string $gameId, string $gameName, string $arena) {
		$this->plugin = Arcade::getAPI();
		$this->gameId = $gameId;
		$this->gameName = $gameName;
		$this->arena = $arena;
		$this->server = $this->plugin->getServer();
		$this->schedule = $this->plugin->getScheduler();
		$this->cfg = new Config($this->plugin->getDataFolder() . "arenas/" . strtolower($this->gameName) . "/" . strtolower($this->arena) . ".yml", Config::YAML);
        $qr = $this->cfg->get("queue");
        if($qr) { // NOT BROKEN, BUT NOT COMPATIBLE WITH MAP RESETTING GAME MODES
            $this->server->loadLevel($qr["level"]);
            if($this->server->isLevelLoaded($qr['level'])) {
                $lvl = $this->server->getLevelByName($qr['level']);
                $pos = new Position($qr['pos-x'], $qr['pos-y'], $qr['pos-z'], $lvl);
                $this->setQueueRoom($pos);
                $this->plugin->getLogger()->info($arena . "'s queue room successfully loaded.");
            }
        }
		$this->creationSuccessEvent();
		$this->queueMatch();
	}


	public function gameId(): string {
		return $this->gameId;
	}

	public function gameName(): string {
		return $this->gameName;
	}

	public function arenaName(): string {
		return $this->arena;
	}

	public function arenaExists() {
		if(file_exists($this->plugin->getDataFolder() . "arenas/" . strtolower($this->gameName) . "/" . strtolower($this->arena) . ".yml")) return true;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setStatus(int $status): void {
		$this->status = $status;
	}

	public function getTaskId() {
	    return $this->taskId;
    }
	
    public function setTaskId($taskId): void {
	    $this->taskId = $taskId;
	}

	public function getTime() {
		return $this->time;
	}

	public function setTime(int $time): void {
		$this->time = $time;
	}

	public function getPrefix(): string {
	    return $this->prefix;
    }

    public function getTimePrefix(): string {
        return $this->timePrefix;
    }

    public function createGameTask($taskName, $timeUnit = 0): void {


		switch($taskName) {

			case "PreGameTask":
			    $this->taskId = $this->schedule->scheduleRepeatingTask(new PreGameTask($this), 20)->getTaskId();
			    break;
			
			case "GameTask":
                switch($timeUnit) {
                    case 0:
                        $this->taskId = $this->schedule->scheduleRepeatingTask(new GameTask($this, 0), 1200)->getTaskId();
                        break;

                    case 1:
                        $this->taskId = $this->schedule->scheduleRepeatingTask(new GameTask($this, 1), 20)->getTaskId();
                        break;

                    default:
                        $this->plugin->getLogger()->info("Invalid GameTask time unit.");
                }
			    break;
		}
	}
	
	public function purgeGameTask(): void {
		if($this->taskId !== false) { 
			$this->schedule->cancelTask($this->taskId);
		}
	}
	
	public function addPosition(Position $pos, $posName, $team = false): void {
		if($team !== false && $this->teamEnabled) {
			if(!isset($this->pos[$team][$posName])) {
				$this->pos[$team][$posName] = $pos;
			}
		}
		elseif(!$team) {
			if(!isset($this->pos[$posName])) $this->pos[$posName] = $pos;
		}
	}

	public function rmPosition($posName, $team = false): void {
		if($team !== false && $this->teamEnabled) {
			if(isset($this->pos[$team][$posName])) {
				unset($this->pos[$team][$posName]);
			}
		}
		elseif(!$team) {
			if(isset($this->pos[$posName])) unset($this->pos[$posName]);
		}
	}

	public function getPosition($posName, $team = false) {
		if($team !== false && $this->teamEnabled) {
			if(isset($this->pos[$team][$posName])) {
				return $this->pos[$team][$posName];
			}
			return false;
		}
		elseif(!$team) {
			return $this->pos[$posName];
		}
		return false;
	}

	public function broadcastMSG($message, $f = 0): void {
		if($this->status == 1) {
			foreach($this->player as $p => $s) {
				$player = $this->server->getPlayerExact($p);             
				if($player instanceof Player) {
					switch($f) {

						case 0:
						    $player->sendMessage($message);
						    break;

						case 1:
						    $player->sendPopup($message);
						    break;

                        case 2:
                            $player->addTitle($message);
                            break;

                        case 3:
                            $player->addTitle($message);
                            $player->getLevel()->addSound(new ClickSound($player->getPosition()));
                            break;

                        case 4:
                            $player->addTitle("", $message);
                            $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
                            break;

						default:
						$player->sendMessage($message);
					}
				}
			}
		}
		elseif($this->status == 0) {
			foreach($this->queue as $p) {
				$player = $this->server->getPlayerExact($p);
				if($player instanceof Player) {
					switch($f) {

                        case 0:
                            $player->sendMessage($message);
                            break;

                        case 1:
                            $player->sendPopup($message);
                            break;

                        case 2:
                            $player->addTitle($message);
                            break;

                        case 3:
                            $player->addTitle($message);
                            $player->getLevel()->addSound(new ClickSound($player->getPosition()));
                            break;

						default:
						$player->sendMessage($message);
					}
				}
			}
		}
	}


	/* Success events are methods that are triggered by the 'Match' methods such as the queueMatch,
	startMatch and so on can be used to trigger a task or simply output a messsage. Please note that
	these success events are crucial for making the mini game progress into different phases such 
	as starting/commencing the match. The Game abstract class is just an outline for actual mini games classes.
	*/

	abstract protected function queueSuccessEvent();

	abstract protected function startSuccessEvent();

	abstract protected function endSuccessEvent();

	abstract protected function creationSuccessEvent();

	/* These secondary events are methods that are triggered by other events, tasks, etc and output simple
	messages or manipulate many other things.
	*/

	abstract protected function queuedPlayerEvent(Player $player);

	abstract public function teleportPlayer(Player $player);

	abstract protected function sufficientQueueEvent();

	abstract protected function forcePlayerEvent(Player $player);

	abstract protected function teleportQueueEvent();

	abstract protected function teamSelectionEvent(Player $player, $team);

	abstract public function activeGameHudEvent(Player $player);

	abstract public function onCertainTime(int $timeUnit, int $time);

	/* BaseListener methods are triggered by the BaseListener class when an event runs on it.
	Are also very important to make the game progress. 
	*/

	abstract public function onBlockBreakEvent(Player $player, Block $block);

	abstract public function onBlockPlaceEvent(Player $player, Block $block);

	abstract public function onPlayerRespawnEvent(Player $player);

	abstract public function onPlayerDamageEvent(Player $player);

	abstract public function onPlayerDeathEvent(Player $player);



	public function queueMatch(): bool {
		if($this->status == 1) {
			$this->plugin->getLogger()->info("Can't start match: Match is active and not queueing players.");
			return false;
		}
		$queueRoom = $this->cfg->get("queue");
		if($queueRoom) {
			if($this->server->isLevelLoaded($queueRoom["level"])) {
				$this->queueSuccessEvent();
				return true;
			}
			$this->plugin->getLogger()->info("By default when a match is queued, the queue room's level is loaded, but it appears that the level can't be loaded. Match could not be queued.");
			return false;
		}
		$lvl = $this->arena;
        if($lvl) {
            $this->server->loadLevel($lvl);
            if($this->server->isLevelLoaded($lvl)) {
                return true;
            }
            $this->plugin->getLogger()->info("By default when a match is queued, the arena's level is loaded, but it appears that the level can't be loaded. Match could not be queued.");
            return false;
        }
		$this->plugin->getLogger()->info("By default when a match is queued, the arena's level is loaded, but it appears that's not possible. Match could not be queued.");
		return false;
	} 

	public function startMatch(): bool {
		if($this->status !== 1) {
			$this->plugin->getLogger()->info("Can't start match: Match is not set to active.");
			return false;
		}
		$this->startSuccessEvent();
		$this->createGameTask("GameTask"); // GAME IS STARTED
		return true;             
	}

	public function endMatch(): bool {
		if($this->status !== 1) {
			$this->plugin->getLogger()->info("Can't end match: Match is not active.");
			return false;
		}
		$this->endSuccessEvent();
        $this->resetMatchData();
	  	$this->queueMatch();
	  	return true;
	}

	public function forcePlayer(Player $player) {
		if($this->status !== 1) {
		    return false;
		}
		$this->plugin->getPlayer($player)->setId($this->gameId);
		$this->plugin->getPlayer($player)->setGamemode($this->gameName());
		if($this->teamEnabled) $this->pickTeam($player);
		$this->forcePlayerEvent($player);
	}

	public function queuePlayer(Player $player) {
		if($this->status == 1) {
			return false;
		}
		if(count($this->getQueues()) < 12) {
			$this->addPlayerQueue($player);
			$player->getInventory()->setItem(8,  Item::get(0, 0, 1));
			$this->plugin->getGFTManager()->updateFloatingTextParticles();
			$this->plugin->getPlayer($player)->setId($this->gameId);
			$this->plugin->getPlayer($player)->setGamemode($this->gameName());
			$this->queuedPlayerEvent($player);
			foreach($this->getQueues() as $p) {
			    $player = $this->server->getPlayerExact($p);
			    $this->plugin->getGFTManager()->despawnFloatingTextParticleTo($player);
                $this->plugin->getGFTManager()->spawnFloatingTextParticleTo($player, $this->gameId);
            }
            if(count($this->getQueues()) == 2) { // CREATE A MINIMUM QUEUE PROPERTY.
                $this->sufficientQueueEvent();
                $this->createGameTask("PreGameTask"); // THIS TRIGGERS THE GAME TO PREPARE TO START
            }
		}
	}

	public function teleportQR(Player $player): void {
		$qRoom = $this->qrRoom;
		$player->teleport($qRoom);
		$this->teleportQueueEvent();
	}

    /**
     * @param Position $position
     */
    public function setQueueRoom(Position $position): void {
        $this->qrRoom = $position;
    }

    /**
     * @return Position
     */

    public function getQueueRoom(): Position {
        if($this->qrRoom instanceof Position) {
            return $this->qrRoom;
        }
        return $this->server->getDefaultLevel()->getSafeSpawn();
    }

	public function pickTeam(Player $player) {
	    if(!$this->teamEnabled) return false;
        $team = $this->team;
        foreach($team as $t => $v) {
            $c = $team[$t]["c"];
            $count[$t] = $c;
        }
        $count = array_flip($count);
        if(count($count) == 1) {
            $flip = array_flip($count);
            $team = key($flip);
        }
        else {
            $flip = array_flip($count);
            $min = min($flip);
            $team = array_search($min, $flip);
        }
		$this->addPlayerCount();
		$this->addTeamCount($team);
		$this->addPlayer($player);
		$this->plugin->getPlayer($player)->setTeam($team);
		$this->teamSelectionEvent($player, $team);
    }

	public function transferMatchData() {
    foreach($this->queue as $username) {
        $p = $this->server->getPlayerExact($username);
        if($p instanceof Player) {
            $this->plugin->getGFTManager()->despawnFloatingTextParticleTo($p);
            if($this->teamEnabled) $this->pickTeam($p);
            $this->teleportPlayer($p); // NEEDS A REWRITE TO MAKE IT ROBUST
        }
    }
    $this->status = 1; //GAME IS NOW ACTIVE
    $this->startMatch();
}

	public function resetMatchData() {
		unset($this->player);
		$this->player = [];
		$this->queue = [];
		$this->status = 0;
		$this->count = 0;
		if($this->teamEnabled) {
			unset($this->team);
		}
	}
	
	public function getQueues() {
		if(isset($this->queue)) {
			return $this->queue;
		}
	}
	
	public function addPlayerQueue(Player $player) {
		$username = strtolower($player->getName());

		if(!isset($this->queue[$username])) {
			$this->queue[$username] = $username;
		}
	}

	public function removePlayerQueue(Player $player) {
		$username = strtolower($player->getName());

		if(isset($this->queue[$username])) {
			unset($this->queue[$username]);
		}
	}
	
	public function queueExists(Player $player) {
		$username = strtolower($player->getName());

		if(isset($this->queue[$username])) {
			return true;
		}
		else return false;
	}

	public function getPlayers() {
		if(isset($this->player)) {
			return $this->player;
		}
	}

	public function addPlayer(Player $player) {
		$username = strtolower($player->getName());

		if(!isset($this->player[$username])) {
			$this->player[$username] = 0; // 0 REPRESENTS STARTING PLAYER SCORE. May not be used in team based games such as CTF.
		}
	}

	public function removePlayer(Player $player) {
		$username = strtolower($player->getName());

		if(isset($this->player[$username])) {
			unset($this->player[$username]);
		}
	}
	
	public function playerExists(Player $player) {
		$username = strtolower($player->getName());

		if(isset($this->player[$username])) {
			return true;
		}
	}

	public function getPlayerScore(Player $player) {
		if($this->scoreEnabled && !$this->teamEnabled) {
		   if($this->playerExists($player)) {
		       $username = strtolower($player->getName());
		       return $this->player[$username];
		   }
		}
	}
	 
	public function getMaxScorePlayer() {
		if(!$this->scoreEnabled) return false;
		$players = $this->player;
		foreach($players as $p => $s) {
            $score[$p] = $s;
        }
        $newScore = array_flip($score);
		if(count($newScore) > 1) {
			$winner = $this->server->getPlayerExact(max($newScore));
			if($winner instanceof Player) {
				return $winner;
			}
			else return "draw";
		}
		else {
			return "draw";
		}
	}

	public function addPlayerPoints(Player $player, $amount) {
		if($this->scoreEnabled && !$this->teamEnabled) {
		
		    if($this->playerExists($player)) {
		       $username = strtolower($player->getName());
		       $current = $this->player[$username];
		       unset($this->player[$username]);
		       $this->player[$username] = $current + $amount;
		   }
		}
	}

	public function subtractPlayerPoints(Player $player, $amount) {
		if($this->scoreEnabled && !$this->teamEnabled) {
		    if($this->playerExists($player)) {
		       $username = strtolower($player->getName());
		       $current = $this->player[$username];
		       unset($this->player[$username]);
		       $this->player[$username] = $current - $amount;
		   }
		}
	}
	
	public function getPlayerCount() {
		return $this->count;
	}

	public function addPlayerCount() {
		$current = $this->count;
		$this->count = $current + 1;
	}

	public function removePlayerCount() {
		$current = $this->count;
		$this->count = $current - 1;
	}

	public function getTeams() {
		if($this->teamEnabled) return $this->team;
	}

	public function getTeam($team) {
		if(!$this->teamEnabled) return false;
		$team = strtolower($team);
		if(isset($this->team[$team])) {
		    return $this->team[$team];
        }
        return false;
	}

	public function createTeam($team) {
		if(!$this->teamEnabled) return false;
		$team = strtolower($team);

		if(!isset($this->team[$team])) {
			$this->team[$team]["c"] = 0; // "c" KEY REPRESENTS PLAYER COUNT 
			if($this->scoreEnabled) $this->team[$team]["s"] = 0; // "s" KEY REPRESENTS TEAM SCORE
		}
	}

	public function delTeam($team) {
		if(!$this->teamEnabled) return false;
		$team = strtolower($team);

		if(isset($this->team[$team])) {
			unset($this->team[$team]);
		}
	}

	public function getTeamCount($team) {
		if($this->teamEnabled) {
			$team = strtolower($team);
			if(isset($this->team[$team])) {
				return $this->team[$team]["c"];
			}
		}
		return false;
	}

	public function addTeamCount($team) {
		if($this->teamEnabled) {
			$team = strtolower($team);
			if(isset($this->team[$team])) {
				$current = $this->team[$team]["c"];
				$this->team[$team]["c"] = $current + 1;
			}
		}
		return false;
	}

	public function removeTeamCount($team) {
		if($this->teamEnabled) {
			$team = strtolower($team);
			if(isset($this->team[$team])) {
				$current = $this->team[$team]["c"];
				$this->team[$team]["c"] = $current - 1;
			}
		}
		return false;
	}
	
	public function getTeamScore($team) {
		if(!$this->scoreEnabled && !$this->teamEnabled) return false;
		$team = strtolower($team);

		if(isset($this->team[$team])) {
			return $this->team[$team]["s"];
		}
	}
	
	public function getMaxScoreTeam() {
		if(!$this->scoreEnabled && !$this->teamEnabled) return false;
		$team = $this->team;
		foreach($team as $t => $v) {
		    $s = $team[$t]["s"];
		    $score[$t] = $s;
		}
        $count = array_flip($score);
        if(count($count) == 1) {
            $team = "draw";
        }
        else {
            $flip = array_flip($count);
            $max = max($flip);
            $team = array_search($max, $flip);
        }
		return $team;
	}

	public function addTeamPoints($amount, $team) {
		if(!$this->scoreEnabled && !$this->teamEnabled) return false;
		$team = strtolower($team);
		$amount = (int)$amount;

		if(isset($this->team[$team])) {
			$current = $this->team[$team]["s"];
			$this->team[$team]["s"] = $current + $amount;
		}
	}

	public function takeTeamPoints($amount, $team) {
		if(!$this->scoreEnabled && !$this->teamEnabled) return false;
		$team = strtolower($team);
		$amount = (int)$amount;

		if(isset($this->team[$team])) {
			$current = $this->team[$team]["s"];
			$this->team[$team]["s"] = $current - $amount;
		}
	}

    public function saveMap(Level $level) {
        $level->save(true);
        $levelPath = $this->server->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $level->getFolderName();
        $zipPath = $this->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $level->getFolderName() . ".zip";
        $zip = new \ZipArchive();
        if(is_file($zipPath)) {
            unlink($zipPath);
        }
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath($levelPath)), \RecursiveIteratorIterator::LEAVES_ONLY);
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if($file->isFile()) {
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename();
                $localPath = substr($filePath, strlen($this->server->getDataPath() . "worlds"));
                $zip->addFile($filePath, $localPath);
            }
        }
        $zip->close();
    }

    public function loadMap(string $folderName): ?Level {
        if(!$this->server->isLevelGenerated($folderName)) {
            return null;
        }
        if($this->server->isLevelLoaded($folderName)) {
            $this->server->getLevelByName($folderName)->unload(true);
        }
        $zipPath = $this->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $folderName . ".zip";
        $zipArchive = new \ZipArchive();
        $zipArchive->open($zipPath);
        $zipArchive->extractTo($this->server->getDataPath() . "worlds");
        $zipArchive->close();
        $this->server->loadLevel($folderName);
        return $this->server->getLevelByName($folderName);
    }
} 