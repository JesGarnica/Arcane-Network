<?php

declare(strict_types=1);

namespace danixl\arcade;

use danixl\arcade\command\Feed;
use danixl\arcade\command\Fly;
use danixl\arcade\command\FT;
use danixl\arcade\command\GetPos;
use danixl\arcade\command\GFT;
use danixl\arcade\command\Kit as KitCmd;
use danixl\arcade\command\Pay;
use danixl\arcade\command\RankCmd;
use danixl\arcade\command\SendCoins;
use danixl\arcade\command\Stats;
use danixl\arcade\command\ViewRank;

use danixl\arcade\listeners\AdminListener;
use danixl\arcade\listeners\BaseListener;

use danixl\arcade\permission\PermissionManager;
use danixl\arcade\permission\Rank;

use danixl\arcade\utils\floatingtext\FTManager;
use danixl\arcade\utils\floatingtext\GFTManager;
use danixl\arcade\utils\form\CustomForm;
use danixl\arcade\utils\scoreboard\Scoreboard;

use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use danixl\arcade\player\Jugador;
use danixl\arcade\player\BossBar;

use danixl\arcade\kit\Kit;

use danixl\arcade\command\Pick;
use danixl\arcade\command\Edit;
use danixl\arcade\command\Manage;
use danixl\arcade\command\Construct;
use danixl\arcade\command\Join;
use danixl\arcade\command\Lobby;

use pocketmine\Player;


class Arcade extends PluginBase {
    
    /*
     * TODO:
     * - Revamp Kit System [X]
     * - Add Kit NPC []
     * - Port Permission System [X]
     * - Port Network Economy [X]
     * - Port Stats [X]
     * - Add Old Paid Ranks Perks [X]
     * - Add Kill Streaks [X]
     * - Record Player Wins and Losses []
     * - Modernize with Form API [X]
     * - CTF 2.0 [X]
     */
     
    public $admin = [], $edit = [], $blocked = [], $onLoading = [], $broadcast;

    public $asrId, $asrTime = 61;

    private $players = [];

    private $manager, $bossBar, $perm, $rank, $kit, $db, $scoreboard, $ftManager, $gftManager;

    public $prefix = "§l§b»§r§a ";

    public $prefixDos = "§l§b»§r§c ";

    public $prefixTres = "§l§b»§r§o§d ";

    private static $instance = null;

    public function onLoad(){
        if(!self::$instance instanceof Arcade) {
            self::$instance = $this;
        }
    }

    public static function getAPI(): Arcade {
        return self::$instance;
    }

    public function onEnable() {
        $this->registerClasses();
        $this->loadConfigurations();
        $this->registerListeners();
        $this->registerCommands();
        $this->registerTasks();
        foreach($this->getServer()->getLevels() as $level) {
            $level->setTime(0);
            $level->stopTime();
        }
    }

    private function loadConfigurations(): void {
        if(file_exists($this->getDataFolder())) {
            if(!file_exists($this->getDataFolder() . "config.yml")) {
                $this->getLogger()->info("§eIt seems like the config file doesn't exist. Creating a new config file...");
                $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
                $data = [
                    "hostname" => "127.0.0.1",
                    "username" => "admin",
                    "password" => "putpassword",
                    "database" => "leaf"
                ];
                $cfg->set("MySQL", $data);
                $cfg->set("game-signs-enabled", false);
                $cfg->save();
            }
            $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
            $this->broadcast = $this->broadcast->get("messages");
        }
        else {
            @mkdir($this->getDataFolder());
            @mkdir($this->getDataFolder() . "arenas/");
            $this->getLogger()->info("Created config file and arenas directory!");
            $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
            $cfg->save();
            $data = [
                "hostname" => "127.0.0.1",
                "username" => "admin",
                "password" => "putpassword",
                "database" => "leafdb"
            ];
            $cfg->set("MySQL", $data);
            $cfg->set("game-signs-enabled", false);
            $cfg->save();
            $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
            $this->broadcast->set("messages", ["message1", "message2", "message3"]);
            $this->broadcast->save();
            $this->broadcast = $this->broadcast->get("messages");
            $this->getLogger()->info("§bCreated config files and player directory!");
        }
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->db = new Database($this, $cfg->get("MySQL"));
        $this->db->loadDatabase();
        $this->getManager()->loadGames();
        if($cfg->get("game-signs-enabled") == true) {
            $this->getManager()->loadGameSigns();
        }
        $this->gftManager = new GFTManager($this); // SEPARATE
    }

    public function broadcastMSG() {
        $randmsg = array_rand($this->broadcast, 1);
        $msg = $this->broadcast[$randmsg];
        $this->getServer()->broadcastMessage("§8[§l§5!§r§8] §o§b" . $msg);
    }

    private function registerClasses(): void {
        $this->manager = new GameManager($this);
        $this->bossBar = new BossBar(TextFormat::BOLD . "§3Arcane Network");
        $this->scoreboard = new Scoreboard($this);
        $this->ftManager = new FTManager($this);
        $this->perm = new PermissionManager($this);
        $this->rank = new Rank($this, $this->perm);
        $this->kit = new Kit();
    }

    public function registerListeners() {
        $this->getServer()->getPluginManager()->registerEvents(new AdminListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BaseListener($this), $this);
    }

    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register(Pick::class, new Pick($this));
        $commandMap->register(Manage::class, new Manage($this));
        $commandMap->register(Edit::class, new Edit($this));
        $commandMap->register(Construct::class, new Construct($this));
        $commandMap->register(Join::class, new Join($this));
        $commandMap->register(RankCmd::class, new RankCmd($this));
        $commandMap->register(Pay::class, new Pay($this));
        $commandMap->register(SendCoins::class, new SendCoins($this));
        $commandMap->register(Stats::class, new Stats($this));
        $commandMap->register(KitCmd::class, new KitCmd($this));
        $commandMap->register(Feed::class, new Feed($this));
        $commandMap->register(Fly::class, new Fly($this));
        $commandMap->register(FT::class, new FT($this));
        $commandMap->register(GFT::class, new GFT($this));
        $commandMap->register(Lobby::class, new Lobby($this));
        $commandMap->register(GetPos::class, new GetPos($this));
        $commandMap->register(ViewRank::class, new ViewRank($this));

    }

    public function registerTasks() {
        $this->asrId = $this->getScheduler()->scheduleRepeatingTask(new task\ASRTask($this, 0), 1200)->getTaskId();
        $this->getScheduler()->scheduleRepeatingTask(new task\BroadcastTask($this), 30 * 20);
        $this->getScheduler()->scheduleRepeatingTask(new task\CheckPlayerTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new task\HudTask($this), 30);

    }

    public function getManager(): GameManager {
        return $this->manager;
    }

    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    public function getFTManager(): FTManager {
        return $this->ftManager;
    }

    public function getGFTManager(): GFTManager {
        return $this->gftManager;
    }

    public function getScoreboard(): Scoreboard {
        return $this->scoreboard;
    }

    public function getPerm() {
        return $this->perm;
    }

    public function getRank() {
        return $this->rank;
    }

    public function getKit(): Kit {
        return $this->kit;
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
        $this->getServer()->getAsyncPool()->submitTask(new task\onQueryTask($queryType, $data, $this->getConfig()->get("MySQL"), $username));
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
                if($player instanceof Player) {
                    $this->getPerm()->setAttachment($player);
                    $this->getPlayer($player)->setRank("primary", $data);
                    $this->getPlayer($player)->setRank("secondary", $data1);
                }
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

    public function sendUIItems(Player $player, string $gameName) {
        $ns = Item::get(399, 0, 1);
        $ns->setCustomName(TextFormat::LIGHT_PURPLE . "My Account");
        $player->getInventory()->setItem(6, $ns);
        $hubItem = Item::get(345, 0, 1);
        $hubItem->setCustomName(TextFormat::AQUA . "Return to Hub");
        $player->getInventory()->setItem(8, $hubItem);
        switch(strtoupper($gameName)) {
            case "CTF":
                $ks = Item::get(421, 0, 1);
                $ks->setCustomName(TextFormat::GREEN . "Class Selector");
                $player->getInventory()->setItem(7, $ks);
                break;

            default:

        }
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
        $rank = ($secondary == "unknown" ? "None" : ucfirst($secondary));
        $form->addLabel("§l§bRank§r§7: §f" . $rank);
        $form->addLabel("§l§6Coins§r§7: §f" . $coins);
        $form->addLabel("§l§cKills§r§7: §f" . $kills);
        $form->addLabel("§l§cDeaths§r§7: §f" . $deaths);
        $form->addLabel("§l§bK/D Ratio§r§7: §f" . $kdr);
        $player->sendForm($form);
    }
}    