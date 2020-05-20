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

class FTManager {

    private $particle = [];
    private $spawnedTo = [];

    private $ftEditor = [];

    private $plugin;
    private $server;

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->loadConfigurations();
    }

    private function loadConfigurations() {
        if(file_exists($this->plugin->getDataFolder() . "particle.json")) {
            $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
            foreach($particleConfig->getAll() as $k => $v) {
                $level = $this->server->getLevelByName($v["pos"][3]);
                $position = new Position($v["pos"][0], $v["pos"][1], $v["pos"][2], $level);
                $levelName = $v["pos"][3];
                $title = str_replace("#", PHP_EOL, $v["title"]);
                $text = str_replace("#", PHP_EOL, $v["text"]);
                if(isset($v["title-visible"])) {
                    if($v["title-visible"] == true) {
                        $this->particle[$levelName][$k] = new FloatingTextParticle($position, $text, $title);
                    }
                    else $this->particle[$levelName][$k] = new FloatingTextParticle($position, $text, false);
                }
                else {
                    $this->particle[$levelName][$k] = new FloatingTextParticle($position, $text, $title);
                }
            }
            $this->plugin->getLogger()->info("Floating text particles loaded...");
        }
        else {
            $this->createDefaults();
        }
    }

    private function createDefaults() {
        $position = $this->server->getDefaultLevel()->getSafeSpawn();
        $string = [$position->getX(), $position->getY(), $position->getZ(), $position->getLevel()->getName()];
        $data = [
            "pos" => $string,
            "title" => "title-example",
            "text" => "Floating Text",
            "title-visible" => true
        ];
        $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
        $particleConfig->set("default-tag", $data);
        $particleConfig->save();
        $level = $position->getLevel()->getName();
        $this->particle[$level]['default-tag'] = new FloatingTextParticle($position, "Floating Text", "title-example");
        $this->plugin->getLogger()->info("Created floating text configuration!");
    }

    public function createFloatingText(string $tag, string $title, string $text, Position $position) {
        $string = [$position->getX(), $position->getY(), $position->getZ(), $position->getLevel()->getName()];
        $data = [
            "pos" => $string,
            "title" => $title,
            "text" => $text,
            "title-visible" => true
        ];
        $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
        $particleConfig->set($tag, $data);
        $particleConfig->save();
        $level = $position->getLevel()->getName();
        $breakTitle = str_replace("#", PHP_EOL, $title);
        $breakText = str_replace("#", PHP_EOL, $text);
        $this->particle[$level][$tag] = new FloatingTextParticle($position, $breakText, $breakTitle);
    }
    
    public function updateFloatingTextConfig(string $level, string $tag, string $title, string $text) {
        $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
        if($particleConfig->exists($tag)) {
            $particleData = $particleConfig->get($tag);
            $pos = $particleData["pos"];
            $data = [
                "pos" => $pos,
                "title" => $title,
                "text" => $text,
                "title-visible" => true
            ];
            $particleConfig->set($tag, $data);
            $particleConfig->save();
            $breakTitle = str_replace("#", PHP_EOL, $title);
            $breakText = str_replace("#", PHP_EOL, $text);
            $this->particle[$level][$tag]->setTitle($breakTitle);
            $this->particle[$level][$tag]->setText($breakText);
        }
    }

    public function removeFloatingTextWithTag(string $tag) {
        $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
        $particleConfig->remove($tag);
        $particleConfig->save();
        foreach($this->particle as $k => $u) {
            if(key($u) == $tag) {
                unset($this->particle[$k][key($u)]);
                return;
            }
        }
    }

    public function removeFloatingText(string $level, string $tag) {
        $particleConfig = new Config($this->plugin->getDataFolder() . "particle.json", Config::JSON);
        $particleConfig->remove($tag);
        $particleConfig->save();
        unset($this->particle[$level][$tag]);
    }

    public function getAllFloatingText() {
        return $this->particle;
    }

    public function getParticle(string $level, string $tag): FloatingTextParticle {
        if(isset($this->particle[$level][$tag])) {
            return $this->particle[$level][$tag];
        }
    }

    public function spawnFloatingTextParticleTo(Player $player) {
        $levelName = $player->getLevel()->getName();
        $this->spawnedTo[$player->getId()] = $levelName;
        if(isset($this->particle[$levelName])) {
            foreach($this->particle[$levelName] as $k => $v) {
                $player->getLevel()->addParticle($this->particle[$levelName][$k], [$player]);
            }
        }
    }

    public function updateFloatingTextParticle(Player $player) {
        if(isset($this->spawnedTo[$player->getId()])) {
            $oldLvlName = $this->spawnedTo[$player->getId()];
            $lvlName = $player->getLevel()->getName();
            if($oldLvlName !== $lvlName) {
                if(isset($this->particle[$lvlName])) {
                    foreach($this->particle[$lvlName] as $k => $v) {
                        $player->getLevel()->addParticle($this->particle[$lvlName][$k], [$player]); // ADDS NEW FT
                        $this->particle[$lvlName][$k]->setInvisible(false);
                    }
                }
            }
            else {
                if(isset($this->particle[$oldLvlName])) {
                    foreach($this->particle[$oldLvlName] as $k => $v) {
                        $this->particle[$oldLvlName][$k]->setInvisible(); // MAKES FT INVISIBLE
                        $player->getLevel()->addParticle($this->particle[$lvlName][$k], [$player]);
                        $this->particle[$lvlName][$k]->setInvisible(false);
                    }
                }
            }
            return;
        }
        $level = $player->getLevel();
        $lvlName = $level->getName();
        $this->spawnedTo[$player->getId()] = $level->getName();
        if(isset($this->particle[$lvlName])) {
            foreach($this->particle[$lvlName] as $k => $v) {
                $player->getLevel()->addParticle($this->particle[$lvlName][$k], [$player]);
            }
        }
    }

    public function createFTManagerUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectFTSettingUI']);
        $form->setTitle("Floating Text Manager");
        $form->setContent("Settings");
        $form->addButton("Create Floating Text", -1, "", "create");
        $form->addButton("View List", -1, "", "list");
        $player->sendForm($form);
    }

    public function selectFTSettingUI(Player $player, ?string $data): void {
        if($data == null) {
            return;
        }
        $setting = $data;
        if($setting == "create") {
            $this->createFTCreationUI($player);
        }
        elseif($setting == "list") {
            $this->createFTLevelListUI($player);
        }
    }

    public function createFTCreationUI(Player $player): void {
        $form = new CustomForm([$this, 'FTCreation']);
        $form->setTitle('Create Floating Text');
        $form->addInput("Enter a tag", "Floating Text Tag Identifier");
        $form->addInput("Enter a title", "unknown", "title-example");
        $form->addInput("Enter text", "unknown", "text-example");
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
        $title = $data[1];
        if(strtolower($title) == "title-example" || empty($title)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter an actual title.");
            return;
        }
        $text = $data[2];
        if($text == "text-example" || empty($text)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter actual text.");
            return;
        }
        $this->createFloatingText($tag, $title, $text, $player->getPosition());
        $this->updateFloatingTextParticle($player);
        $player->sendMessage(TextFormat::GRAY . "[Arcane] Created floating text with tag: " . $tag);
    }

    public function createFTLevelListUI(Player $player): void {
        $particles = $this->getAllFloatingText();
        if(!empty($particles)) {
            $form = new SimpleForm([$this, 'selectFTLevelUI']);
            $form->setTitle("Floating Text Level List");
            foreach($particles as $k => $u) {
                $form->addButton($k, -1, "", $k);
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
        $form->setTitle("Floating Text Particle List");
        $form->setContent("Select a particle to edit or delete");
        $level = $data;
        $particle = $this->particle;
        foreach($particle[$level] as $k => $u) {
            $form->addButton($k, -1, "", $k . ":" . $level);
        }
        $player->sendForm($form);
    }

    public function selectFTUI(Player $player, ?string $data): void {
        if ($data == null) {
            return;
        }
        $break = explode(":" , $data);
        $tag = $break[0];
        $level = $break[1];
        $form = new SimpleForm([$this, 'specificFTUI']);
        $form->setTitle("Floating Text Manager");
        $form->setContent("Tag: " . $tag);
        $form->addButton(TextFormat::YELLOW . "Edit", -1, "", "edit:" . $tag . ":" . $level);
        $form->addButton(TextFormat::RED . "Delete", -1, "", "delete:" . $tag . ":" . $level);
        $player->sendForm($form);
    }
    public function specificFTUI(Player $player, ?string $data): void {
        if ($data == null) {
            return;
        }
        $break = explode(":", $data);
        $option = $break[0];
        $tag = $break[1];
        $level = $break[2];

        if($option == "edit") {
            $this->editFTUI($player, $tag . ":" . $level);
        }
        elseif($option == "delete") {
            $this->removeFloatingText($level, $tag);
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Deleted floating text with tag: " . $tag);
        }
    }

    public function editFTUI(Player $player, ?string $data): void {
        if ($data == null) {
            return;
        }
        $break = explode(":", $data);
        $tag = $break[0];
        $level = $break[1];
        $particle = $this->getParticle($level, $tag);
        $form = new CustomForm([$this, 'editFT']);
        $form->setTitle('Floating Text Manager');
        $form->addLabel("Tag:" . $tag);
        $form->addInput("Edit Title", "entra un titulo...", $particle->getTitle());
        $form->addInput("Edit Text", "entra un texto...", $particle->getText());
        if(isset($this->ftEditor[$player->getName()])) {
            unset($this->ftEditor[$player->getName()]);
        }
        $this->ftEditor[$player->getName()] = [$tag, $level];
        $player->sendForm($form);
    }

    public function editFT(Player $player, ?array $data): void {
        if($data == null) {
            return;
        }
        if(isset($this->ftEditor[$player->getName()])) {
            $tag = $this->ftEditor[$player->getName()][0];
            $level = $this->ftEditor[$player->getName()][1];
            unset($this->ftEditor[$player->getName()]);
            $particle = $this->getParticle($level, $tag);
            $oldTitle = $particle->getTitle();
            $oldText = $particle->getText();

            $title = $data[1];
            if(empty($title)) {
                $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter an actual title.");
                return;
            }
            $text = $data[2];
            if(empty($text)) {
                $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter actual text.");
                return;
            }
            if($title == $oldTitle && $text == $oldText) {
                $player->sendMessage(TextFormat::GRAY . "[Arcane] You cannot have the same title and text. No changes made.");
                return;
            }
            $this->updateFloatingTextConfig($level, $tag, $title, $text);
            $this->updateFloatingTextParticle($player);
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Updated floating text with tag: " . $tag);
        }
    }
}