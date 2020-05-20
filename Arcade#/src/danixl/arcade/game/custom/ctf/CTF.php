<?php

declare(strict_types=1);

/*
 * Capture the Flag or CTF for short is a simple game that is self-explanatory.
 * This is the very first game made for Arcade that uses most of the Arcade Game API.
 */


namespace danixl\arcade\game\custom\ctf;

use danixl\arcade\game\Game;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\item\Armor;

use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\utils\Color;

use pocketmine\level\Position;

use pocketmine\Player;

use pocketmine\block\Block;

use pocketmine\item\Item;

use pocketmine\utils\TextFormat;

class CTF extends Game {

	protected $teamEnabled = true, $teamColor = [], $armor = [
        Armor::LEATHER_CAP,
        Armor::LEATHER_TUNIC,
        Armor::LEATHER_LEGGINGS,
        Armor::LEATHER_BOOTS
    ];

	private $hasFlag = [], $flagTaskId;

	private $chatTags = ["§8[§9BLUE§8]", "§8[§cRED§8]"];



	public function creationSuccessEvent() {
	    $this->gameName = "CTF";
        $this->teamColor["blue"] = new Color(0,128,255);
        $this->teamColor["red"] = new Color(255,0,0);
        $this->syncPositions();
        $this->restoreFlags();

	}
	
	public function queueSuccessEvent() {
        $this->createTeam("blue");
        $this->createTeam("red");
		$this->server->broadcastMessage("§8[§3*§8] §2CTF Match§8: §c" . ucfirst($this->arena) . " §3Now Available!");
	}
	
	public function startSuccessEvent() {
		$this->time = 9; // 8 minutes but task subtracts by 1.
        $this->startHeldFlagTask();
	}

    public function endSuccessEvent() {
		$team = $this->getMaxScoreTeam();
		$players = $this->player;
		if(count($players) >= 1) {
			$default = $this->server->getDefaultLevel()->getSafeSpawn();
			foreach($players as $p => $s) {
				$player = $this->server->getPlayerExact($p);
				if($player instanceof Player) {
					$player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
					$player->setHealth(20);
					$player->setFood(20);
                    $this->plugin->sendUIItems($player, "CTF");
                    $this->plugin->getPlayer($player)->setId(false);
                    $this->plugin->getPlayer($player)->setGamemode(false);
					$this->plugin->getPlayer($player)->setTeam(false);
					$this->updateTeamFormat($player);
                    $this->plugin->getKit()->rmKitUsed($player);
					$player->setNameTag($player->getName());
					$player->setSpawn($default);
					$player->teleport($default);
					$player->sendMessage($this->plugin->prefixTres . "The match has been concluded!");
					if($team == "draw") {
                        $player->addTitle("", "§lIT'S A DRAW...!", 60, 80, 20);
                    }
					else {
					    if($team == "blue") {
					        $text = "§lWINNING TEAM IS §9" . strtoupper($team) . "!";
                        }
                        else {
                            $text = "§lWINNING TEAM IS §c" . strtoupper($team) . "!";
                        }
                        $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
					    $player->addTitle("", $text, 60, 80, 20);
                    }
				}
			}
		}
		$this->restoreFlags();
		$this->stopHeldFlagTask();
		return true;
	}

	public function queuedPlayerEvent(Player $player) {
		$this->teleportQR($player);
        $player->sendMessage($this->plugin->prefix . "You've been queued for the opening match!");
	}

    public function teleportPlayer(Player $player) {
        $team = $this->plugin->getPlayer($player)->getTeam();

        if($team == "blue") {
            $blue = $this->getPosition("spawn", "blue");
            $player->teleport($blue);
        }
        if($team == "red") {
            $red = $this->getPosition("spawn", "red");
            $player->teleport($red);
        }
        if($this->plugin->getKit()->kitUsed($player)) {
            $this->plugin->getKit()->giveKitUsed($player);
            $kit = $this->plugin->getKit()->getKitUsed($player);
            $this->giveKitEffect($player, $kit);
        }
        else {
            $this->sendTeamKit($player);
        }
	}

	public function sufficientQueueEvent() {
        $this->time = 31; // 60 seconds but task subtracts by 1.
	}

	public function forcePlayerEvent(Player $player) {
        $team = $this->plugin->getPlayer($player)->getTeam();

        if($team == "blue") {
            $blue = $this->getPosition("spawn", "blue");
            $player->teleport($blue);
        }
        if($team == "red") {
            $red = $this->getPosition("spawn", "red");
            $player->teleport($red);
        }
        if($this->plugin->getKit()->kitUsed($player)) {
            $this->plugin->getKit()->giveKitUsed($player);
            $kit = $this->plugin->getKit()->getKitUsed($player);
            $this->giveKitEffect($player, $kit);
        }
        else {
            $this->sendTeamKit($player);
        }
	}

	public function teleportQueueEvent() {

	}

	public function teamSelectionEvent(Player $player, $team) {
	    $this->updateTeamFormat($player, $team);
	    if($team == "blue") {
	        $blue = $this->getPosition("spawn", "blue");
	        $player->setNameTag("§8[§9BLUE§8] §f" . $player->getName());
	        $player->setSpawn($blue);
        }
        elseif($team == "red") {
            $red = $this->getPosition("spawn", "red");
            $player->setNameTag("§8[§cRED§8] §f" . $player->getName());
            $player->setSpawn($red);
        }
        $player->sendMessage($this->plugin->prefix . "You've been assigned to " . $team . " team");
	}

    public function updateTeamFormat(Player $player, $team = false) {
        $p = $this->plugin->getPlayer($player);
        $format = $p->getChatFormat();

        if($team) {
            if($team == "blue") {
                $team = "§8[§9BLUE§8]§3";
                $newFormat = str_replace("#", $team, $format);
            }
            else {
                $team = "§8[§cRED§8]§3";
                $newFormat = str_replace("#", $team, $format);
            }
        }
        else {
            $newFormat = str_replace($this->chatTags, "#", $format);
        }
        $p->setChatFormat($newFormat);
    }

	public function activeGameHudEvent(Player $player) {
	    $b = $this->getTeamScore("blue");
	    $r = $this->getTeamScore("red");
	    $f = TextFormat::BOLD . '§9BLUE§8:§f ' . $b . ' §cRED§8:§f ' . $r;
        $player->sendPopup($f);
	}

	public function onCertainTime(int $timeUnit, int $time)
    {
        // TODO: Implement onCertainTime() method.
    }

    public function onBlockBreakEvent(Player $player, Block $block) {
        if($this->status !== 1) {
            return false;
        }

        $blue = $this->getPosition("flag","blue");
        $red = $this->getPosition("flag", "red");


        if(!$blue || !$red) {
            return false;
        }

        $team = $this->plugin->getPlayer($player)->getTeam();
        $pos = new Position($block->x,$block->y, $block->z, $block->getLevel());
        switch($team) {

            case "blue":
                if($pos == $red) {
                    if($this->hasFlag("red")) {
                        $held = $this->getHeldFlagUser("red");
                        if($held !== $player->getName()) {
                            $player->sendMessage($this->plugin->prefix .  $held . " is holding the RED flag. Guide them!");
                            return false;
                        }
                    }
                    $item = Item::get(35, 14, 1);
                    $item->setCustomName(TextFormat::BOLD . TextFormat::RED . "RED FLAG");
                    if($player->getInventory()->contains($item)) {
                        $player->sendMessage($this->plugin->prefixDos .  "You have already retrieved the RED flag! Make your move!");
                        return false;
                    }
                    $player->getInventory()->addItem($item);
                    $lvl = $player->getLevel()->getId();
                    $replaceBlock = new Block(0, 0);
                    $this->server->getLevel($lvl)->setBlock($red, $replaceBlock);
                    $this->addFlag($player);
                    $this->broadcastMSG("§9" . $player->getName() . "§f picked up the §cRED FLAG", 4);

                }
                break;

            case "red":
                if($pos == $blue) {
                    if($this->hasFlag("blue")) {
                        $held = $this->getHeldFlagUser("blue");
                        if($held !== $player->getName()) {
                            $player->sendMessage($this->plugin->prefix . $held . " is holding the BLUE flag. Guide them!");
                            return false;
                        }
                    }
                    $item = Item::get(35, 11, 1);
                    $item->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "BLUE FLAG");
                    if($player->getInventory()->contains($item)) {
                        $player->sendMessage($this->plugin->prefixDos . "You have already retrieved the BLUE flag! Make your move!");
                        return false;
                    }
                    $player->getInventory()->addItem($item);
                    $lvl = $player->getLevel()->getId();
                    $replaceBlock = new Block(0, 0);
                    $this->server->getLevel($lvl)->setBlock($blue, $replaceBlock);
                    $this->addFlag($player);
                    $this->broadcastMSG("§c" . $player->getName() . "§f picked up the §9BLUE FLAG", 4);
                }
                break;
        }
	}

	public function onBlockPlaceEvent(Player $player, Block $block) {
        if($this->status !== 1) {
            return false;
        }

        $blue = $this->getPosition("return","blue");
        $red = $this->getPosition("return", "red");

        if(!$blue || !$red) {
            return false;
        }

        $team = $this->plugin->getPlayer($player)->getTeam();
        $pos = new Position($block->x,$block->y, $block->z, $block->getLevel());

        switch($team) {

            case "blue":
                if($pos == $blue) {
                    $inv = $player->getInventory();
                    $name = TextFormat::BOLD . TextFormat::RED . "RED FLAG";
                    if($inv->getItemInHand()->getCustomName() === $name) {
                        $player->getInventory()->removeItem($inv->getItemInHand());
                        $this->rmFlag("red");
                        $red = $this->getPosition("flag", "red");
                        $restoreBlock = new Block(35, 14);
                        $lvl = $player->getLevel()->getId();
                        $this->server->getLevel($lvl)->setBlock($red, $restoreBlock);
                        $this->addTeamPoints(1, "blue");
                        $this->broadcastMSG("§9" . $player->getName() . "§f captured the §cRED FLAG!", 4);
                    }
                    else $player->sendMessage($this->plugin->prefixDos . "Place a red flag in order to score.");
                }
                break;

            case "red":
                if($pos == $red) {
                    $inv = $player->getInventory();
                    $name = TextFormat::BOLD . TextFormat::BLUE . "BLUE FLAG";
                    if($inv->getItemInHand()->getCustomName() === $name) {
                        $player->getInventory()->removeItem($inv->getItemInHand());
                        $this->rmFlag("blue");
                        $blue = $this->getPosition("flag", "blue");
                        $restoreBlock = new Block(35, 11);
                        $lvl = $player->getLevel()->getId();
                        $this->server->getLevel($lvl)->setBlock($blue, $restoreBlock);
                        $this->addTeamPoints(1, "red");
                        $this->broadcastMSG("§c" . $player->getName() . "§f captured the §9BLUE FLAG!", 4);
                    }
                    else $player->sendMessage($this->plugin->prefixDos . " Place a blue flag in order to score.");
                }
                break;
        }

	}

	public function onPlayerRespawnEvent(Player $player) { // Implement all these events once other gamemodes are added.
	    if($this->plugin->getKit()->kitUsed($player)) {
            $this->plugin->getKit()->giveKitUsed($player);
            $kit = $this->plugin->getKit()->getKitUsed($player);
            $this->giveKitEffect($player, $kit);
        }
        else {
            $this->sendTeamKit($player);
        }
        $this->plugin->sendUIItems($player, "CTF");
	}

	public function onPlayerDamageEvent(Player $player) {

	}

	public function onPlayerDeathEvent(Player $player) {

	}

	private function getLvl($lvl) {
	    return $this->server->getLevelByName($lvl);
    }

	public function syncPositions() {
		$blueFlag = $this->cfg->get("blue-flag");
		$this->addPosition(new Position($blueFlag["pos-x"], $blueFlag["pos-y"], $blueFlag["pos-z"], $this->getLvl($blueFlag["level"])), "flag", "blue");
		$blueReturn = $this->cfg->get("blue-return");
		$this->addPosition(new Position($blueReturn["pos-x"], $blueReturn["pos-y"] + 1, $blueReturn["pos-z"], $this->getLvl($blueReturn["level"])), "return", "blue");
		$blueSpawn = $this->cfg->get("blue-spawn");
		$this->addPosition(new Position($blueSpawn["pos-x"], $blueSpawn["pos-y"], $blueSpawn["pos-z"], $this->getLvl($blueSpawn["level"])), "spawn", "blue");
		$redFlag = $this->cfg->get("red-flag");
		$this->addPosition(new Position($redFlag["pos-x"], $redFlag["pos-y"], $redFlag["pos-z"], $this->getLvl($redFlag["level"])), "flag", "red");
		$redReturn = $this->cfg->get("red-return");
		$this->addPosition(new Position($redReturn["pos-x"], $redReturn["pos-y"] + 1, $redReturn["pos-z"], $this->getLvl($redReturn["level"])), "return", "red");
		$redSpawn = $this->cfg->get("red-spawn");
		$this->addPosition(new Position($redSpawn["pos-x"], $redSpawn["pos-y"], $redSpawn["pos-z"], $this->getLvl($redSpawn["level"])), "spawn", "red");
	}


    public function getHeldFlagUser($team) {
        if($this->getTeam($team)) {
            if(isset($this->hasFlag[$team])) {
                return $this->hasFlag[$team];
            }
            return false;
        }
        return false;
    }

    public function hasFlag($team) {
        if($this->getTeam($team)) {
            if(isset($this->hasFlag[$team])) {
                return true;
            }
            return false;
        }
        return false;
    }

	public function addFlag(Player $player) {
	    $username = $player->getName();
	    $p = $this->plugin->getPlayer($player);
	    $team = $p->getTeam();

	    if($team == "blue") {
	        $this->hasFlag["red"] = $username;
        }
        elseif($team == "red") {
	        $this->hasFlag["blue"] = $username;
        }
    }

    public function rmFlag($team) {
        if($this->getTeam($team)) {
            if(isset($this->hasFlag[$team])) {
                unset($this->hasFlag[$team]);
            }
            return false;
        }
        return false;
    }

    public function getAllFlagData() {
	    return $this->hasFlag;
    }

    public function restoreFlags() {
	    $blue = $this->getPosition("flag", "blue");
	    $red = $this->getPosition("flag", "red");
	    $blueLvl = $blue->getLevel()->getId();
	    $redLvl = $red->getLevel()->getId();
	    $newBlock = new Block(35, 11);
	    $this->server->getLevel($blueLvl)->setBlock($blue, $newBlock);
	    $newBlock = new Block(35, 14);
        $this->server->getLevel($redLvl)->setBlock($red, $newBlock);
        $this->plugin->getLogger()->info($this->gameId . ": Creating flags...");
    }

    public function restoreOpposingTeamFlag($team, $username, $reason) {
	    if($team == "blue") {
	        if($this->hasFlag("red")) {
	            $held = $this->getHeldFlagUser("red");
	            if($username == $held) {
                    $this->rmFlag("red");
                    $red = $this->getPosition("flag", "red");
                    $redLvl = $red->getLevel()->getId();
                    $newBlock = new Block(35, 14);
                    $this->server->getLevel($redLvl)->setBlock($red, $newBlock);
                    $this->broadcastMSG($this->plugin->prefix . " " . $reason . " RED flag has returned to its team!");
                    return true;
                }
            }
            return true;
        }
        if($team == "red") {
            if($this->hasFlag("blue")) {
                $held = $this->getHeldFlagUser("red");
                if($username == $held) {
                    $this->rmFlag("blue");
                    $blue = $this->getPosition("flag", "blue");
                    $blueLvl = $blue->getLevel()->getId();
                    $newBlock = new Block(35, 11);
                    $this->server->getLevel($blueLvl)->setBlock($blue, $newBlock);
                    $this->broadcastMSG($this->plugin->prefix . " " . $reason . " BLUE flag has returned to its team!");
                    return true;
                }
            }
        }
        return false;
    }

    public function startHeldFlagTask() {
        $this->flagTaskId = $this->schedule->scheduleRepeatingTask(new HeldFlagTask($this), 25)->getTaskId();
    }

    public function stopHeldFlagTask() {
	    if($this->flagTaskId) {
	        $this->schedule->cancelTask($this->flagTaskId);
        }
    }


	public function sendTeamKit(Player $player) {
	    $p = $this->plugin->getPlayer($player);
	    $team = $p->getTeam();
	    switch($team) {
            case "blue":
                $color = $this->teamColor["blue"];
                break;

            case "red":
                $color = $this->teamColor["red"];
                break;
        }
        $cacheArmor = $this->armor;
        foreach($cacheArmor as $armor) {
            $a = Armor::get($armor);
            $a->setCustomColor($color);
            if($armor == Armor::LEATHER_CAP) {
                $player->getArmorInventory()->setHelmet($a);
            }
            if($armor == Armor::LEATHER_CHESTPLATE) {
                $player->getArmorInventory()->setChestplate($a);
            }
            if($armor == Armor::LEATHER_LEGGINGS) {
                $player->getArmorInventory()->setLeggings($a);
            }
            if($armor == Armor::LEATHER_BOOTS) {
                $player->getArmorInventory()->setBoots($a);
            }
        }
        $player->getInventory()->addItem(Item::get(272, 0, 1));
        $player->getInventory()->addItem(Item::get(364, 0, 4));
        $player->getInventory()->addItem(Item::get(261, 0, 1));
        $player->getInventory()->addItem(Item::get(262, 0, 16));
        $this->plugin->sendUIItems($player, "CTF");
    }

    public function giveKitEffect(Player $player, string $kit) {

	    switch($kit) {
            case "astronaut":
                $effect = Effect::getEffect(8);
                $effect = new EffectInstance($effect);
                $effect->setDuration(12000);
                $effect->setVisible(false);
                $effect->setAmplifier(0);
                $player->addEffect($effect);
                break;

            case "goblin":
                $effect = Effect::getEffect(12);
                $effect = new EffectInstance($effect);
                $effect->setDuration(12000);
                $effect->setVisible(false);
                $effect->setAmplifier(2);
                $player->addEffect($effect);
                $effect = Effect::getEffect(3);
                $effect = new EffectInstance($effect);
                $effect->setDuration(12000);
                $effect->setVisible(false);
                $effect->setAmplifier(0);
                $player->addEffect($effect);
                break;
        }
    }
}