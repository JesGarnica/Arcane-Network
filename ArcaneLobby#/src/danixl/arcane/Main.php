<?php

namespace danixl\arcane;

use danixl\arcane\command\FT;
use danixl\arcane\player\Jugador;

use danixl\arcane\command\Edit;
use danixl\arcane\command\Pay;
use danixl\arcane\command\RankCmd;
use danixl\arcane\command\SendCoins;
use danixl\arcane\command\Stats;
use danixl\arcane\listeners\BaseListener;

use danixl\arcane\listeners\SlapperListener;
use danixl\arcane\permission\PermissionManager;
use danixl\arcane\permission\Rank;

use danixl\arcane\servers\ServerStats;

use danixl\arcane\task\onQueryTask;

use danixl\arcane\utils\BossBar;

use danixl\arcane\utils\floatingtext\FTManager;

use danixl\arcane\utils\form\CustomForm;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    public $broadcast, $bossBar, $mysql;

    public $onLoading = [], $players = [], $edit = [];

    private $rank, $perm, $serverStats, $ftManager, $db;

    public $asrId, $asrTime = 61;

    private static $instance = null;

    public function onLoad(){
        if(!self::$instance instanceof Main) {
            self::$instance = $this;
        }
    }

    public static function getAPI(): Main {
        return self::$instance;
    }

    public function onEnable() {
        $this->loadConfigurations();
        $this->registerClasses();
        $this->registerListeners();
        $this->registerCommands();
        $this->registerTasks();
        $this->getServer()->getDefaultLevel()->setTime(14000);
        $this->getServer()->getDefaultLevel()->stopTime();
    }

    private function loadConfigurations(): void {
        if(file_exists($this->getDataFolder())) {
            if(!file_exists($this->getDataFolder() . "mysql.yml")) {
                $this->getLogger()->info("§eIt seems like the config file doesn't exist. Creating a new config file...");
                $mysql = new Config($this->getDataFolder() . "mysql.yml", Config::YAML);
                $data = [
                    "hostname" => "127.0.0.1",
                    "username" => "admin",
                    "password" => "putpassword",
                    "database" => "leaf"
                ];
                $mysql->set("MySQL", $data);
                $mysql->save();
                $this->mysql = $data;
            }
            $this->mysql = (new Config($this->getDataFolder() . "mysql.yml", Config::YAML))->get("MySQL");
            $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
            $this->broadcast = $this->broadcast->get("messages");
        }
        else {
            @mkdir($this->getDataFolder());
            $this->getLogger()->info("Created config file and arenas directory!");
            $mysql = new Config($this->getDataFolder() . "mysql.yml", Config::YAML);
            $data = [
                "hostname" => "127.0.0.1",
                "username" => "admin",
                "password" => "putpassword",
                "database" => "leafdb"
            ];
            $mysql->set("MySQL", $data);
            $mysql->save();
            $this->mysql = $data;
            $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
            $this->broadcast->set("messages", ["message1", "message2", "message3"]);
            $this->broadcast->save();
            $this->broadcast = $this->broadcast->get("messages");
            $this->getLogger()->info("§bCreated config files and player directory!");
        }
        $this->db = new Database($this, $this->mysql);
        $this->db->loadDatabase();
    }

    private function registerClasses(): void {
        $this->bossBar = new BossBar("§l§bArcane §fNetwork");
        $this->serverStats = new ServerStats($this);
        $this->ftManager = new FTManager($this);
        $this->perm = new PermissionManager($this);
        $this->rank = new Rank($this, $this->perm);
    }

    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register(Edit::class, new Edit($this));
        $commandMap->register(RankCmd::class, new RankCmd($this));
        $commandMap->register(Pay::class, new Pay($this));
        $commandMap->register(SendCoins::class, new SendCoins($this));
        $commandMap->register(Stats::class, new Stats($this));
        $commandMap->register(FT::class, new FT($this));

    }

    private function registerListeners() {
        $this->getServer()->getPluginManager()->registerEvents(new BaseListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SlapperListener($this), $this);
    }

    public function registerTasks() {
        $this->asrId = $this->getScheduler()->scheduleRepeatingTask(new task\ASRTask($this, 0), 1200)->getTaskId();
        $this->getScheduler()->scheduleRepeatingTask(new task\BroadcastTask($this), 30 * 20);
        $this->getScheduler()->scheduleRepeatingTask(new task\CheckPlayerTask($this), 20);

    }

    public function broadcastMSG() {
        if($this->broadcast) {
            $randmsg = array_rand($this->broadcast, 1);
            $msg = $this->broadcast[$randmsg];
            $this->getServer()->broadcastMessage("§8[§l§5!§r§8] §o§b" . $msg);
        }
    }

    public function getServerStats(): ServerStats {
        return $this->serverStats;
    }

    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    public function getFTManager(): FTManager {
        return $this->ftManager;
    }

    public function getPerm() {
        return $this->perm;
    }

    public function getRank() {
        return $this->rank;
    }

    public function createPlayerUI(Player $player) {
        $username = $player->getName();
        $form = new CustomForm(null);
        $form->setTitle($username . "'s Info");
        $data = $this->getPlayer($player);
        $kills = $data->getKills();
        $deaths = $data->getDeaths();
        $kdr = ($kills >= 1 && $deaths >= 1 ? round($kills/$deaths, 2) : 0);
        $coins = $data->getCoins();
        $secondary = $data->playerRank("secondary");
        $rank = ($secondary == "unknown" ? "None" : $secondary);
        $form->addLabel("§l§bRank§r§7: §f" . $rank);
        $form->addLabel("§l§6Coins§r§7: §f" . $coins);
        $form->addLabel("§l§cKills§r§7: §f" . $kills);
        $form->addLabel("§l§cDeaths§r§7: §f" . $deaths);
        $form->addLabel("§l§bK/D Ratio§r§7: §f" . $kdr);
        $player->sendForm($form);
    }

    public function getPlayer(Player $player): Jugador {
        $username = strtolower($player->getName());

        if(isset($this->players[$username])) {
            return $this->players[$username];
        }
    }

    public function createPlayer(Player $player): void  {
        $username = strtolower($player->getName());

        if(!isset($this->players[$username])) {
            $this->players[$username] = new Jugador($this, $player);
        }
    }

    public function destroyPlayer(Player $player): void {
        $username = strtolower($player->getName());

        if(isset($this->players[$username])) {
            unset($this->players[$username]);
        }
    }

    public function queryTask($queryType, $data, $username = null){
        $low_username = strtolower($username);
        $this->addPlayerOnTask($low_username, $queryType);
        $this->getServer()->getAsyncPool()->submitTask(new onQueryTask($queryType, $data, $this->mysql, $username));
    }

    public function isPlayerStillOnTask($username, $task) {
        $low_username = strtolower($username);

        if(isset($this->onLoading[$low_username][$task])) {
            return true;
        }
        else{
            return false;
        }
    }

    public function addPlayerOnTask($username, $task){
        $low_username = strtolower($username);
        if(isset($this->onLoading[$low_username][$task])){
            $this->onLoading[$low_username][$task] = $task;
        }else{
            $this->onLoading[$low_username][$task] = $task;
        }
    }

    public function rmPlayerOnTask($username, $task){
        $low_username = strtolower($username);
        if(isset($this->onLoading[$low_username][$task])){
            unset($this->onLoading[$low_username][$task]);
        }
    }

    public function playerRegistered($username) {
        $defaultRank = $this->getRank()->getPrimaryDefault();
        $this->queryTask("playerRegistered", $defaultRank, $username);
    }

    public function queryTaskDone($queryType, $username, $data, $data1 = null){
        $player = $this->getServer()->getPlayerExact($username);
        $low_username = strtolower($username);
        $this->rmPlayerOnTask($low_username, $queryType);

        switch($queryType){

            case "syncKD":
                $this->getPlayer($player)->setKills($data);
                $this->getPlayer($player)->setDeaths($data1);
                break;

            case "syncCoins":
                $this->getPlayer($player)->setCoins($data);
                break;

            case "syncRanks":
                $this->getPerm()->setAttachment($player);
                $this->getPlayer($player)->setRank("primary", $data);
                $this->getPlayer($player)->setRank("secondary", $data1);
                break;

            case "saveRanks":
                $this->getLogger()->info($username . "'s ranks have been saved.");
                break;

            case "playerRegistered":
                $this->queryTask("syncRanks", null, $username);
                $this->queryTask("syncCoins", null, $username);
                $this->queryTask("syncKD", null, $username);
                break;
        }
    }
}