<?php
namespace danixl\fac;

use danixl\fac\command\AreaCmd;
use danixl\fac\command\DeleteHome;
use danixl\fac\command\DelWarp;
use danixl\fac\command\Edit;
use danixl\fac\command\FactionCmd;
use danixl\fac\command\Feed;
use danixl\fac\command\Fly;
use danixl\fac\command\FT;
use danixl\fac\command\GetPos;
use danixl\fac\command\Heal;
use danixl\fac\command\Home;
use danixl\fac\command\Info;
use danixl\fac\command\Kit;
use danixl\fac\command\Lobby;
use danixl\fac\command\Pay;
use danixl\fac\command\RankCmd;
use danixl\fac\command\Repair;
use danixl\fac\command\SendCoins;
use danixl\fac\command\SetCoins;
use danixl\fac\command\SetHome;
use danixl\fac\command\SetWarp;
use danixl\fac\command\Spawn;
use danixl\fac\command\Stats;
use danixl\fac\command\TPA;
use danixl\fac\command\TPAccept;
use danixl\fac\command\TPDecline;
use danixl\fac\command\ViewRank;
use danixl\fac\command\Warp;

use danixl\fac\faction\FactionManager;
use danixl\fac\listener\AreaListener;
use danixl\fac\listener\EventListener;
use danixl\fac\player\Ghost;
use danixl\fac\player\Skeleton;
use danixl\fac\task\ASRTask;
use danixl\fac\task\BroadcastTask;
use danixl\fac\task\CheckPlayerTask;
use danixl\fac\task\KitManagerTask;

use danixl\fac\utils\form\CustomForm;
use danixl\fac\utils\form\SimpleForm;
use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;

use pocketmine\math\Vector3;

use danixl\fac\utils\floatingtext\FTManager;

use danixl\fac\area\AreaManager;

use danixl\fac\permission\PermissionManager;
use danixl\fac\permission\Rank;
use danixl\fac\essential\Warp as W;
use danixl\fac\essential\Kit as K;
use danixl\fac\essential\DropParty;

use danixl\fac\player\Jugador;

use pocketmine\Player;

use pocketmine\item\Item;
use pocketmine\item\Armor;
use pocketmine\item\Tool;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {
	
	public $tpRequests = [], $onLoading = [], $logId = [], $edit = [];

	private $faction, $area, $ftManager;

	private $perm, $rank, $warp, $kit, $dropParty, $broadcast, $db;

	private $players = [], $ghost;

    public $prefix = "§l§b»§r§a ";

    public $prefixDos = "§l§b»§r§c ";

    public $prefixTres = "§l§b»§r§o§d ";

	public $asrId, $asrTime;

	public static $instance = null;

    public function onLoad(){
        if(!self::$instance instanceof Main) {
            self::$instance = $this;
        }
    }

	public function onEnable() {
		$this->loadConfigurations();
		$this->registerClasses();
		$this->registerCommands();
		$this->registerTasks();
		$this->registerListeners();
		$this->ghost = new Ghost("Steve");
	}

    private function loadConfigurations(): void {
        if(file_exists($this->getDataFolder())) {
            if(!file_exists($this->getDataFolder() . "broadcast.yml")) {
                $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
                $this->broadcast->set("messages", ["message1", "message2", "message3"]);
                $this->broadcast->save();
            }
            else {
                $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
                $this->broadcast = $this->broadcast->get("messages");
            }
        }
        else {
            @mkdir($this->getDataFolder());
            $this->getLogger()->info("Created config file and arenas directory!");
            $this->broadcast = new Config($this->getDataFolder() . "broadcast.yml", Config::YAML);
            $this->broadcast->set("messages", ["message1", "message2", "message3"]);
            $this->broadcast->save();
            $this->broadcast = ['Welcome to your new server!'];
            $this->getLogger()->info("§bCreated config files and player directory!");
        }
        $this->db = new Database($this);
        $this->db->loadDatabase();
        $this->db->loadFactionDatabase();
        $this->getServer()->loadLevel("main");
    }

	public static function getInstance(): Main {
		return self::$instance;
	}

	public function getFac(): FactionManager {
	    return $this->faction;
    }

	public function getArea(): AreaManager {
	    return $this->area;
    }

    public function getFTManager(): FTManager {
        return $this->ftManager;
    }

	public function getPerm(): PermissionManager {
		return $this->perm;
	}

	public function getRank(): Rank {
		return $this->rank;
	}

	public function getWarp(): W {
		return $this->warp;
	}

    public function getKit(): K {
        return $this->kit;
    }

    public function getDropParty(): DropParty {
        return $this->dropParty;
    }

    private function registerClasses(): void {
        //$this->scoreboard = new Scoreboard($this);
        $this->faction = new FactionManager($this);
        $this->area = new AreaManager($this);
        $this->ftManager = new FTManager($this);
        $this->perm = new PermissionManager($this);
        $this->rank = new Rank($this, $this->perm);
        $this->warp = new W($this);
        $this->kit = new K($this);
        //$this->dropParty = new DropParty($this);
        //$this->dropParty->loadDropParty();
        //$this->loadTasks();
    }

    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register(FactionCmd::class, new FactionCmd($this));
        $commandMap->register(AreaCmd::class, new AreaCmd($this));
        $commandMap->register(Edit::class, new Edit($this));
        $commandMap->register(RankCmd::class, new RankCmd($this));
        $commandMap->register(Warp::class, new Warp($this));
        $commandMap->register(SetWarp::class, new SetWarp($this));
        $commandMap->register(DelWarp::class, new DelWarp($this));
        $commandMap->register(Pay::class, new Pay($this));
        $commandMap->register(SendCoins::class, new SendCoins($this));
        $commandMap->register(SetCoins::class, new SetCoins($this));
        $commandMap->register(Stats::class, new Stats($this));
        $commandMap->register(Kit::class, new Kit($this));
        $commandMap->register(Feed::class, new Feed($this));
        $commandMap->register(Fly::class, new Fly($this));
        $commandMap->register(FT::class, new FT($this));
        $commandMap->register(Lobby::class, new Lobby($this));
        $commandMap->register(Spawn::class, new Spawn($this));
        $commandMap->register(GetPos::class, new GetPos($this));
        $commandMap->register(ViewRank::class, new ViewRank($this));
        $commandMap->register(Heal::class, new Heal($this));
        $commandMap->register(Home::class, new Home($this));
        $commandMap->register(SetHome::class, new SetHome($this));
        $commandMap->register(DeleteHome::class, new DeleteHome($this));
        $commandMap->register(TPA::class, new TPA($this));
        $commandMap->register(TPAccept::class, new TPAccept($this));
        $commandMap->register(TPDecline::class, new TPDecline($this));
        $commandMap->register(Repair::class, new Repair($this));
        $commandMap->register(Info::class, new Info($this));
    }

    private function registerTasks() {
        $this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), 30 * 20);
        $this->asrId = $this->getScheduler()->scheduleRepeatingTask(new ASRTask($this, 0), 1200)->getTaskId();
        $this->getScheduler()->scheduleRepeatingTask(new KitManagerTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new CheckPlayerTask($this), 30);
    }

    private function registerListeners() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new AreaListener($this), $this);

    }

    public function broadcastMSG() {
	    if(!empty($this->broadcast)) {
            $randomMessage = array_rand($this->broadcast, 1);
            $msg = $this->broadcast[$randomMessage];
            $this->getServer()->broadcastMessage("§8[§l§5!§r§8] §o§b" . $msg);
        }
    }

    public function getPlayer(?Player $player): Skeleton {
	    if($player == null) {
	        return $this->ghost;
        }
        else {
            $username = strtolower($player->getName());

            if(isset($this->players[$username])) {
                return $this->players[$username];
            }
            else {
                return $this->ghost;
            }
        }
    }

    public function createPlayer(Player $player): void {
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
        $this->getServer()->getAsyncPool()->submitTask(new task\onQueryTask($queryType, $data, $username));
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

            case "syncFaction":
                if($data) {
                    if($this->getFac()->factionExists($data)) {
                        $faction = $this->getFac()->getFaction($data);
                        if($faction->isMember($username)) {
                            $rank = $faction->getMemberRank($username);
                            $this->getPlayer($player)->setFaction($data);
                            $this->getPlayer($player)->setFactionRank($rank);
                            $leader = $faction->getLeader();
                            if(strtolower($leader) == $low_username) {
                                $this->getPlayer($player)->setFactionLeader(true);
                            }
                            return;
                        }
                    }
                }
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
                if($data == 'account_exists') {
                    $this->queryTask("syncFaction", null, $username);
                    $this->queryTask("syncRanks", null, $username);
                    $this->queryTask("syncCoins", null, $username);
                    $this->queryTask("syncKD", null, $username);
                }
                /*else {
                    $p = $this->getPlayer($player);
                    $p->setRank('primary', $this->getRank()->getPrimaryDefault(), false);
                    $p->setRank('secondary', 'unknown', false);
                }*/
                break;

            case "deletePlayerFac":
                if($data) {
                    $player->sendMessage($this->prefix . "You have successfully kicked " . TextFormat::LIGHT_PURPLE . strtoupper($data) . TextFormat::GREEN . " from the faction.");
                }
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

	public function isRepairable(Item $item): bool{
        return $item instanceof Tool || $item instanceof Armor;
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
        $faction = $data->getFaction();
        $faction = ($faction == null ? "None" : $faction);
        $form->addLabel("§l§bRank§r§7: §f" . $rank . "\n§l§aFaction§r§7: §f" . $faction . "\n\n§l§6Coins§r§7: §f" . $coins . "\n\n§l§cKills§r§7: §f" .
            $kills . "\n§l§cDeaths§r§7: §f" . $deaths . "\n§l§eK/D Ratio§r§7: §f" . $kdr);
        $player->sendForm($form);
    }
}    