<?php
namespace danixl\arcade\utils\floatingtext;


use danixl\arcade\Arcade;
use danixl\arcade\utils\form\CustomForm;
use danixl\arcade\utils\form\SimpleForm;

use pocketmine\Player;

use pocketmine\level\Position;
use pocketmine\level\particle\FloatingTextParticle;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class GFTManager {

    /* Game Floating Text Manager (GFTManager) shares very similar architecture, but is completely independent
       from FTManager.
    */

    private $particle = [];
    private $spawnedTo = [];

    private $plugin;
    private $server;

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->loadConfigurations();
    }

    private function loadConfigurations() {
        if(file_exists($this->plugin->getDataFolder() . "gparticle.json")) {
            $particleConfig = new Config($this->plugin->getDataFolder() . "gparticle.json", Config::JSON);
            foreach($particleConfig->getAll() as $k => $v) {
                $level = $this->server->getLevelByName($v["pos"][3]);
                $position = new Position($v["pos"][0], $v["pos"][1], $v["pos"][2], $level);
                $id = $v["id"];
                $gamemode = $v["gamemode"];

                switch($gamemode) {

                    case "CTF":
                        $title = "§l§cCapture §9the §fFlag";
                        break;

                    default:
                        $title = "§l§bArcane §f" . $gamemode;
                }
                if($this->plugin->getManager()->gameExists($id)) {
                    $game = $this->plugin->getManager()->getGame($id);
                    $arena = ucfirst($this->plugin->getManager()->getGame($id)->arenaName());
                    if($game->getStatus() == 0) {
                        $status = "§aQueueing §7» §2" . $arena;
                        $players = "§dPlayers Waiting§7: §f" . count($game->getQueues()) . "§7/§f12";
                    }
                    else {
                        $status = "§cUnavailable";
                        $players = null;
                    }
                    $text = $status . PHP_EOL . $players;
                    $this->particle[$id][$k] = new FloatingTextParticle($position, $text, $title);
                }
                else {
                    $this->plugin->getLogger()->info("Could not load game status particle for Game ID: " . $id);
                }
                //$this->plugin->getScheduler()->scheduleRepeatingTask(new GFTTask($this), 45);
            }
            $this->plugin->getLogger()->info("Game floating text particles loaded...");
        }
    }

    // Game Based Floating Text

    public function createFloatingText(string $tag, string $id, string $gamemode, Position $position) {
        $string = [$position->getX(), $position->getY(), $position->getZ(), $position->getLevel()->getName()];
        $data = [
            "id" => $id,
            "gamemode" => $gamemode,
            "pos" => $string
        ];
        $particleConfig = new Config($this->plugin->getDataFolder() . "gparticle.json", Config::JSON);
        $particleConfig->set($tag, $data);
        $particleConfig->save();

        switch($gamemode) {

            case "CTF":
                $title = "§cCapture §9the §fFlag";
                break;

            default:
                $title = "§l§bArcane §f" . $gamemode;
        }
        if($this->plugin->getManager()->gameExists($id)) {
            $game = $this->plugin->getManager()->getGame($id);
            $arena = ucfirst($this->plugin->getManager()->getGame($id)->arenaName());
            if($game->getStatus() == 0) {
                $status = "§aQueueing §7» §2" . $arena;
                $players = "§dPlayers Waiting§7: §f" . count($game->getQueues()) . "§7/§f12";
            }
            else {
                $status = "§cUnavailable";
                $players = null;
            }
            $text = $status . PHP_EOL . $players;
        }
        else {
            return;
        }
        $this->particle[$id][$tag] = new FloatingTextParticle($position, $text, $title);
    }


    public function removeFloatingText(string $id, string $tag) {
        $particleConfig = new Config($this->plugin->getDataFolder() . "gparticle.json", Config::JSON);
        $particleConfig->remove($tag);
        $particleConfig->save();
        unset($this->particle[$id][$tag]);
    }

    public function getAllFloatingText() {
        return $this->particle;
    }

    public function getParticle(string $id, string $tag): FloatingTextParticle {
        if(isset($this->particle[$id][$tag])) {
            return $this->particle[$id][$tag];
        }
    }

    public function getSpawnedTo() {
        return $this->spawnedTo;
    }

    public function spawnFloatingTextParticleTo(Player $player, string $id) {
        if(!isset($this->spawnedTo[$player->getName()])) {
            $this->spawnedTo[$player->getName()] = $id;
            if(isset($this->particle[$id])) {
                foreach($this->particle[$id] as $k => $v) {
                    $player->getLevel()->addParticle($this->particle[$id][$k], [$player]);
                }
            }
        }
    }

    public function despawnFloatingTextParticleTo(Player $player) {
        if(isset($this->spawnedTo[$player->getName()])) {
            $id = $this->spawnedTo[$player->getName()];
            if(isset($this->particle[$id])) {
                foreach($this->particle[$id] as $k => $v) {
                    $this->particle[$id][$k]->setInvisible();
                }
            }
            unset($this->spawnedTo[$player->getName()]);
        }
    }

    public function updateFloatingTextParticles() {
        if(!empty($this->particle)) {
            foreach($this->particle as $k => $t) {
                if($this->plugin->getManager()->gameExists($k)) {
                    $game = $this->plugin->getManager()->getGame($k);
                    $arena = ucfirst($this->plugin->getManager()->getGame($k)->arenaName());
                    if($game->getStatus() == 0) {
                        $status = "§aQueueing §7» §2" . $arena;
                        $players = "§dPlayers Waiting§7: §f" . count($game->getQueues()) . "§7/§f12";
                    }
                    else {
                        $status = "§cUnavailable";
                        $players = null;
                    }
                    $text = $status . PHP_EOL . $players;
                    $tag = key($t);
                    $this->particle[$k][$tag]->setText($text);
                 }
            }
        }
    }

    public function createFTManagerUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectFTSettingUI']);
        $form->setTitle("Game Floating Text Manager");
        $form->setContent("Settings");
        $form->addButton("Create Game Floating Text", -1, "", "create");
        $form->addButton("View List", -1, "", "list");
        $player->sendForm($form);
    }

    public function selectFTSettingUI(Player $player, ?string $data): void {
        if($data == null) {
            return;
        }
        $setting = $data;
        switch($setting) {

            case "create":
                $this->createFTCreationUI($player);
                break;
            case "list":
                $this->createFTLevelListUI($player);
                break;
        }
    }


    public function createFTCreationUI(Player $player): void {
        $form = new CustomForm([$this, 'FTCreation']);
        $form->setTitle('Create Game Floating Text');
        $form->addInput("Enter a tag", "Floating Text Tag Identifier");
        $form->addInput("Enter Game ID", "unknown", "ID");
        $form->addDropdown("Gamemode", ["CTF", "INF"], 0, "gamemode");
        $player->sendForm($form);
    }

    public function FTCreation(Player $player, ?array $data): void {
        if($data == null) {
            return;
        }
        $tag = $data[0];
        if(strtolower($tag) == "tag-example" || empty($tag)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter an actual tag.");
            return;
        }
        $gameId = $data[1];
        if(strtolower($gameId) == "ID" || empty($gameId)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter an actual Game ID.");
            return;
        }
        if(!$this->plugin->getManager()->gameExists($gameId)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Game with ID: " . $gameId . " does not exist.");
            return;
        }
        $gamemode = ($data["gamemode"] == 0 ? "CTF" : "INF"); // TODO: SWITCH CONDITION STATEMENT INSTEAD.
        $this->createFloatingText($tag, $gameId, $gamemode, $player->getPosition());
        $player->sendMessage(TextFormat::GRAY . "[Arcane] Created game floating text with tag: " . $tag . " with Game ID:" . $gameId);
    }

    public function createFTLevelListUI(Player $player): void {
        $particles = $this->getAllFloatingText();
        if(!empty($particles)) {
            $form = new SimpleForm([$this, 'selectFTLevelUI']);
            $form->setTitle("Game Floating Text ID List");
            foreach($particles as $id => $t) {
                $form->addButton($id, -1, "", $id);
            }
            $player->sendForm($form);
        }
        else {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] No particles have been found.");
        }
    }

    public function selectFTLevelUI(Player $player, ?string $data): void {
        if($data == null) {
            return;
        }
        $form = new SimpleForm([$this, 'selectFTUI']);
        $form->setTitle("Game Floating Text Particle List");
        $form->setContent("Select a particle to manage");
        $id = $data;
        $particle = $this->particle;
        foreach($particle[$id] as $t) {
            $form->addButton($t, -1, "", $t . ":" . $id);
        }
        $player->sendForm($form);
    }

    public function selectFTUI(Player $player, ?string $data): void {
        if ($data == null) {
            return;
        }
        $break = explode(":" , $data);
        $tag = $break[0];
        $id = $break[1];
        $form = new SimpleForm([$this, 'specificFTUI']);
        $form->setTitle("Game Floating Text Manager");
        $form->setContent("Tag: " . $tag);
        $form->addButton(TextFormat::RED . "Delete", -1, "", "delete:" . $tag . ":" . $id);
        $player->sendForm($form);
    }
    public function specificFTUI(Player $player, ?string $data): void {
        if ($data == null) {
            return;
        }
        $break = explode(":", $data);
        $option = $break[0];
        $tag = $break[1];
        $id = $break[2];

        /*if($option == "edit") {
            $this->editFTUI($player, $tag . ":" . $level);
        }*/
        if($option == "delete") {
            $this->removeFloatingText($id, $tag);
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Deleted game floating text with tag: " . $tag);
        }
    }
}