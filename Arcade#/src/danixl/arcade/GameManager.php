<?php

namespace danixl\arcade;

use danixl\arcade\game\custom;

use danixl\arcade\game\Game;
use danixl\arcade\listeners\game\CTFListener;
use danixl\arcade\listeners\game\INFListener;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class GameManager {

    private $plugin, $server;

    private $games = [];

    private $gameData = [];

    public $signPositions = [];

    public $waitingMatch = [];

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
    }

    public function createGame(string $id, string $arena, string $gameName) {
        $gameName = strtoupper($gameName);
        if(count($this->games) > 8) {
            $this->plugin->getLogger()->info("Game instance limit of 8 per server has been reached.\n
                Game instance could not be created.");
            return false;
        }
        if(!$this->arenaExists($gameName, $arena)) {
            $this->plugin->getLogger()->info("Arena doesn't exist.");
            return false;
        }
        switch($gameName) {

            case "CTF":
                $this->plugin->getServer()->broadcastMessage("Loaded CTF game instance with ID: " . $id);
                $this->games[$id] = new custom\ctf\CTF($id, "CTF", $arena);
                return $id;
                break;

            case "INF":
                $this->plugin->getServer()->broadcastMessage("Loaded Infected game instance with ID: " . $id);
                $this->games[$id] = new custom\inf\Infected($id, "INF", $arena);
                break;

            case "SW":
                $this->plugin->getLogger()->info("SkyWars gamemode not set up yet.");
                break;

            default:
                $this->plugin->getLogger()->info("Gamemode class wasn't found, therefore, game instance couldn't be created.");
        }
    }

    public function getGame($id): Game {
        if(isset($this->games[$id])) {
            return $this->games[$id];
        }
    }

    public function destroyGame(string $id): void {
        if(isset($this->games[$id])) {
            unset($this->games[$id]);
        }
    }

    public function gameExists($id): bool {
        if(isset($this->games[$id])) {
            return true;
        }
        else {
            return false;
        }
    }

    public function loadGames() {
        if(file_exists($this->plugin->getDataFolder() . "games.yml")) {
            $cfg = new Config($this->plugin->getDataFolder() . "games.yml", Config::YAML);
            $gamesEnabled = [];
            foreach($cfg->getAll() as $id => $gameData) {
                $this->gameData[$id]['arena'] = $gameData['arena'];
                $this->gameData[$id]['gm'] = $gameData["gm"];
                $this->createGame($id, $gameData['arena'], $gameData['gm']);
                if(!isset($gamesEnabled[$gameData["gm"]])) {
                    switch($gameData["gm"]) {
                        case "CTF":
                            $this->server->getPluginManager()->registerEvents(new CTFListener($this->plugin), $this->plugin);
                            $this->plugin->getBossBar()->setTitle(TextFormat::BOLD . "§3Arcane §cC§9T§7F");
                            break;

                        case "INF":
                            $this->server->getPluginManager()->registerEvents(new INFListener($this->plugin), $this->plugin);
                            $this->plugin->getBossBar()->setTitle(TextFormat::BOLD . "§3Arcane §cInfected");
                            break;
                    }
                }
            }
        }
        else {
            $this->plugin->getLogger()->info("No games could be loaded as games config doesn't exist.");
        }
    }

    public function loadGameSigns() {
        if(file_exists($this->plugin->getDataFolder() . "signs.yml")) {
            $cfg = new Config($this->plugin->getDataFolder() . "signs.yml", Config::YAML);
            $gamesEnabled = [];
            foreach($cfg->getAll() as $k => $signData) {
                $pos = $signData["pos"];
                $this->server->loadLevel($pos["level"]);
                if($this->server->isLevelLoaded($pos["level"])) {
                    $level = $this->server->getLevelByName($pos["level"]);
                    $position = new Position($pos["pos-x"], $pos["pos-y"], $pos["pos-z"], $level);
                    $this->signPositions[$k]['arena'] = $signData['arena'];
                    $this->signPositions[$k]['pos'] = $position;
                    $this->signPositions[$k]['gm'] = $signData["gm"];
                    if(!isset($this->games[$k])) {
                        $this->createGame($k, $signData['arena'], $signData['gm']);
                    }
                    if(!isset($gamesEnabled[$signData["gm"]])) {
                        switch($signData["gm"]) {
                            case "CTF":
                                $this->server->getPluginManager()->registerEvents(new CTFListener($this->plugin), $this->plugin);
                                break;

                            case "INF":
                                $this->server->getPluginManager()->registerEvents(new INFListener($this->plugin), $this->plugin);
                                break;
                        }
                    }
                    $this->plugin->getLogger()->info("[ID: " . $k . "] sign data loaded with arena: " . $signData['arena']);
                }
                else {
                    $this->plugin->getLogger()->warning("Level is not valid for [ID: " . $k .  "] sign positions.");
                }
            }
            $this->plugin->getScheduler()->scheduleRepeatingTask(new task\SignRefreshTask($this->plugin), 90);
        }
        else {
            $cfg = new Config($this->plugin->getDataFolder() . "signs.yml", Config::YAML);
        }
    }

    public function findNextAvailableGame(Player $player) {
        if(empty($this->games)) {
            $player->sendMessage($this->plugin->prefixDos . "No available matches.");
            $player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
            return;
        }
        $username = strtolower($player->getName());
        if(!isset($this->waitingMatch[$username])) {
            $player->sendMessage($this->plugin->prefix . "Looking for an available match...");
            $this->waitingMatch[$username] = true;
            $potentialMatch = [];
            if(!$this->plugin->getPlayer($player)->hasId()) {
                foreach($this->games as $game) {
                    if($game->getStatus() == 0) {
                        if(count($game->getQueues()) >= 1) {
                            $game->queuePlayer($player);
                            unset($this->waitingMatch[$username]);
                            return;
                        }
                        else {
                            array_push($potentialMatch, $game->gameId());
                        }
                    }
                }
                if(isset($potentialMatch)) {
                    $randId = array_rand($potentialMatch, 1);
                    $game = $this->games[$potentialMatch[$randId]];
                    $game->queuePlayer($player);
                    unset($this->waitingMatch[$username]);
                    return;
                }
                if($player->hasPermission("arc.forcejoin")) {
                    $player->sendMessage($this->plugin->prefix . "Could not find a match. Forcing match join...");
                    $this->games[0]->forcePlayer($player);
                    unset($this->waitingMatch[$username]);
                    return;
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . "Could not find a match.\nWait until the next available match.");
                    $player->sendMessage($this->plugin->prefixTres .  "Tired of waiting? Force Join any match with a rank.\nPurchase one at http://arcn.us");
                    unset($this->waitingMatch[$username]);
                    $player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
                }
            }
        }
    }

    public function arenaExists(string $gameName, string $arena): bool {
        if(file_exists($this->plugin->getDataFolder() . "arenas/" . strtolower($gameName) . "/" . strtolower($arena) . ".yml")) {
            return true;
        }
        return false;
    }

    public function arenaOccupied($arena): bool {
        if(empty($this->games)) {
            return false;
        }
        foreach($this->games as $id => $game) {
            $a = $game->getArena();
            if($arena === $a) return true;
        }
        return false;
    }
}