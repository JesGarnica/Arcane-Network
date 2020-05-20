<?php
namespace danixl\arcade\game\custom\inf;

use danixl\arcade\game\Game;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BedWars extends Game {

    protected $teamEnabled = true;

    private $chatTags = ["§8[§9BLUE§8]", "§8[§cRED§8]", "§8[§aGREEN§8]", "§8[§eYELLOW§8]"];


    public function creationSuccessEvent() {
        $this->gameName = "BW";
        $this->prefix = "§8[§cBedWars§8]§f";
        $this->timePrefix = "§8[§eBedWars§8]§7";
        $this->syncPositions();
    }

    public function queueSuccessEvent() {
        $this->createTeam("blue");
        $this->createTeam("red");
        $this->createTeam("green");
        $this->createTeam("yellow");
        $this->server->broadcastMessage("§8[§3*§8] §cBedWars §fMatch§8: §c" . ucfirst($this->arena) . " §aNow Available!");
    }

    public function sufficientQueueEvent() {
       $this->time = 21;
    }

    public function startSuccessEvent() {
        $this->time = 21;
    }

    public function endSuccessEvent() {
        $players = $this->player;
        if(count($players) >= 1) {
            $default = $this->server->getDefaultLevel()->getSafeSpawn();
            foreach($players as $p => $s) {
                $player = $this->server->getPlayerExact($p);
                if($player instanceof Player) {
                    $player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->setHealth(20);
                    $this->plugin->sendUIItems($player, "BW");
                    $this->plugin->getPlayer($player)->setId(false);
                    $this->plugin->getPlayer($player)->setGamemode(false);
                    $this->plugin->getPlayer($player)->setTeam(false);
                    $this->updateTeamFormat($player);
                    $player->setNameTag($player->getName());
                    $player->setSpawn($default);
                    $player->teleport($default);
                    $winner = $this->getWinner();
                    $player->sendMessage($this->plugin->prefix . " The match has been concluded!");
                    $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
                    $player->addTitle("", $winner[1], 60, 80, 20);
                }
            }
        }
    }


    public function activeGameHudEvent(Player $player) {
        $b = $this->getTeamCount("blue");
        $r = $this->getTeamCount("red");
        $g = $this->getTeamCount("");
        $f = TextFormat::BOLD . '§cZombies Left§8:§f ' . $b . ' §eSurvivors Left§8:§f ' . $r;
        $player->sendPopup($f);
    }

    public function updateTeamFormat(Player $player, $team = false) {
        $p = $this->plugin->getPlayer($player);
        $format = $p->getChatFormat();

        if($team) {
            switch($team) {
                case "blue":
                    $team = "§8[§9BLUE§8]§3";
                    $newFormat = str_replace("#", $team, $format);
                    break;

                case "red":
                    $team = "§8[§cRED§8]§3";
                    $newFormat = str_replace("#", $team, $format);
                    break;

                case "green":
                    $team = "§8[§aGREEN§8]§3";
                    $newFormat = str_replace("#", $team, $format);
                    break;

                case "yellow":
                    $team = "§8[§eYELLOW§8]§3";
                    $newFormat = str_replace("#", $team, $format);
                    break;
            }
        }
        else {
            $newFormat = str_replace($this->chatTags, "#", $format);
        }
        $p->setChatFormat($newFormat);
    }

    public function teamSelectionEvent(Player $player, $team) {
        $this->updateTeamFormat($player, $team);

        switch($team) {

            case "blue":
                $b = $this->getPosition("spawn", "blue");
                $player->setNameTag("§8[§9BLUE§8] §f" . $player->getName());
                $player->setSpawn($b);
                break;

            case "red":
                $r = $this->getPosition("spawn", "red");
                $player->setNameTag("§8[§cRED§8] §f" . $player->getName());
                $player->setSpawn($r);
                break;

            case "green":
                $g = $this->getPosition("spawn", "green");
                $player->setNameTag("§8[§aGREEN§8] §f" . $player->getName());
                $player->setSpawn($g);
                break;

            case "yellow":
                $p = $this->getPosition("spawn", "yellow");
                $player->setNameTag("§8[§eYELLOW§8] §f" . $player->getName());
                $player->setSpawn($p);
                break;
        }
        $player->addTitle("", " You are on " . ucfirst($team) . " team!", 40,  70, 40);
        $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
    }

    public function teleportQueueEvent()
    {
        // TODO: Implement teleportQueueEvent() method.
    }

    public function queuedPlayerEvent(Player $player) {
        $this->teleportQR($player);
        $player->sendMessage($this->plugin->prefix . " You've been queued for the match!");
    }

    public function teleportPlayer(Player $player) {
        $team = $this->plugin->getPlayer($player)->getTeam();

        switch($team) {

            case "blue":
                $b = $this->getPosition("spawn", "blue");
                $player->teleport($b);
                break;

            case "red":
                $r = $this->getPosition("spawn", "red");
                $player->teleport($r);
                break;

            case "green":
                $g = $this->getPosition("spawn", "green");
                $player->teleport($g);
                break;

            case "yellow":
                $p = $this->getPosition("spawn", "yellow");
                $player->teleport($p);
                break;
        }
    }

    public function forcePlayerEvent(Player $player) {
        $this->teleportPlayer($player);
    }

    public function onPlayerRespawnEvent(Player $player) {
        // TODO: Implement onPlayerRespawnEvent() method.
    }

    public function onPlayerDeathEvent(Player $player) {
        // TODO: Implement onPlayerDeathEvent() method.
    }

    public function onPlayerDamageEvent(Player $player) {
        // TODO: Implement onPlayerDamageEvent() method.
    }

    public function onBlockPlaceEvent(Player $player, Block $block) {

    }

    public function onBlockBreakEvent(Player $player, Block $block) {

    }

    private function getLvl($lvl) {
        return $this->server->getLevelByName($lvl);
    }

    public function syncPositions() {
        // Blue Positions
        $blueSpawn = $this->cfg->get("blue-spawn");
        $this->addPosition(new Position($blueSpawn["pos-x"], $blueSpawn["pos-y"], $blueSpawn["pos-z"], $this->getLvl($blueSpawn["level"])), "spawn", "blue");
        $blueBed = $this->cfg->get("blue-bed");
        $this->addPosition(new Position($blueBed["pos-x"], $blueBed["pos-y"], $blueBed["pos-z"], $this->getLvl($blueBed["level"])), "bed", "blue");
        $blueIronGenerator = $this->cfg->get("blue-iron");
        $this->addPosition(new Position($blueIronGenerator["pos-x"], $blueIronGenerator["pos-y"], $blueIronGenerator["pos-z"], $this->getLvl($blueIronGenerator["level"])), "iron", "blue");
        $blueGoldGenerator = $this->cfg->get("blue-gold");
        $this->addPosition(new Position($blueGoldGenerator["pos-x"], $blueGoldGenerator["pos-y"], $blueGoldGenerator["pos-z"], $this->getLvl($blueGoldGenerator["level"])), "gold", "blue");
        // Red Positions
        $redSpawn = $this->cfg->get("red-spawn");
        $this->addPosition(new Position($redSpawn["pos-x"], $redSpawn["pos-y"], $redSpawn["pos-z"], $this->getLvl($redSpawn["level"])), "spawn", "red");
        $redBed = $this->cfg->get("red-bed");
        $this->addPosition(new Position($redBed["pos-x"], $redBed["pos-y"], $redBed["pos-z"], $this->getLvl($redBed["level"])), "red", "red");
        $redIronGenerator = $this->cfg->get("red-iron");
        $this->addPosition(new Position($redIronGenerator["pos-x"], $redIronGenerator["pos-y"], $redIronGenerator["pos-z"], $this->getLvl($redIronGenerator["level"])), "iron", "red");
        $redGoldGenerator = $this->cfg->get("red-gold");
        $this->addPosition(new Position($redGoldGenerator["pos-x"], $redGoldGenerator["pos-y"], $redGoldGenerator["pos-z"], $this->getLvl($redGoldGenerator["level"])), "gold", "red");
        // Green Positions
        $greenSpawn = $this->cfg->get("green-spawn");
        $this->addPosition(new Position($greenSpawn["pos-x"], $greenSpawn["pos-y"], $greenSpawn["pos-z"], $this->getLvl($greenSpawn["level"])), "spawn", "green");
        $greenBed = $this->cfg->get("green-bed");
        $this->addPosition(new Position($greenBed["pos-x"], $greenBed["pos-y"], $greenBed["pos-z"], $this->getLvl($greenBed["level"])), "bed", "green");
        $greenIronGenerator = $this->cfg->get("green-iron");
        $this->addPosition(new Position($greenIronGenerator["pos-x"], $greenIronGenerator["pos-y"], $greenIronGenerator["pos-z"], $this->getLvl($greenIronGenerator["level"])), "iron", "green");
        $greenGoldGenerator = $this->cfg->get("green-gold");
        $this->addPosition(new Position($greenGoldGenerator["pos-x"], $greenGoldGenerator["pos-y"], $greenGoldGenerator["pos-z"], $this->getLvl($greenGoldGenerator["level"])), "gold", "green");
        // Yellow Positions `
        $yellowSpawn = $this->cfg->get("yellow-spawn");
        $this->addPosition(new Position($yellowSpawn["pos-x"], $yellowSpawn["pos-y"], $yellowSpawn["pos-z"], $this->getLvl($yellowSpawn["level"])), "spawn", "yellow");
        $yellowBed = $this->cfg->get("yellow-bed");
        $this->addPosition(new Position($yellowBed["pos-x"], $yellowBed["pos-y"], $yellowBed["pos-z"], $this->getLvl($yellowBed["level"])), "bed", "yellow");
        $yellowIronGenerator = $this->cfg->get("yellow-iron");
        $this->addPosition(new Position($yellowIronGenerator["pos-x"], $yellowIronGenerator["pos-y"], $yellowIronGenerator["pos-z"], $this->getLvl($yellowIronGenerator["level"])), "iron", "yellow");
        $yellowGoldGenerator = $this->cfg->get("yellow-gold");
        $this->addPosition(new Position($yellowGoldGenerator["pos-x"], $yellowGoldGenerator["pos-y"], $yellowGoldGenerator["pos-z"], $this->getLvl($yellowGoldGenerator["level"])), "gold", "yellow");
        // Other Positions
        foreach($this->cfg->get("diamond-gen") as $name => $p) {
            $pos = new Position($p["pos-x"], $p["pos-y"], $p["pos-z"], $this->getLvl($p["level"]));
            $this->addPosition($pos, $name);
        }
        foreach($this->cfg->get("emerald-gen") as $name => $p) {
            $pos = new Position($p["pos-x"], $p["pos-y"], $p["pos-z"], $this->getLvl($p["level"]));
            $this->addPosition($pos, $name);
        }
    }
}