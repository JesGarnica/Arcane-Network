<?php
namespace danixl\arcade\game\custom\inf;

use danixl\arcade\game\Game;

use pocketmine\block\Block;

use pocketmine\entity\Skin;

use pocketmine\level\Position;

use pocketmine\level\sound\EndermanTeleportSound;

use pocketmine\Player;

class Infected extends Game {

    protected $teamEnabled = true;

    private $chatTags = ["§8[§cZombie§8]", "§8[§eSurvivor§8]"];

    private $skinData = [];

    private $preInfected = [];

    public function creationSuccessEvent() {
        $this->gameName = "INF";
        $this->syncPositions();
    }

    public function queueSuccessEvent() {
        $this->createTeam("zombie");
        $this->createTeam("survivor");
        $this->server->broadcastMessage("§8[§c*§8] §3Infected Match§8: §c" . ucfirst($this->arena) . " §aNow Available!");
    }

    public function sufficientQueueEvent() {
       $this->time = 21;
    }

    public function startSuccessEvent() {
        $this->time = 6;
        $this->schedule->scheduleDelayedRepeatingTask(new InfectPlayerTask($this), 30 * 20, 0);
        $this->schedule->scheduleRepeatingTask(new InfectTask($this), 20);
        $this->broadcastMSG($this->plugin->prefixDos . "Infection will spread in 30 seconds...");
        $this->broadcastMSG($this->plugin->prefix . "Run and hide now!");

    }

    public function endSuccessEvent() {
        $players = $this->player;
        $allPlayers = [];
        if(count($players) >= 1) {
            $default = $this->server->getDefaultLevel()->getSafeSpawn();
            foreach($players as $p => $s) {
                $player = $this->server->getPlayerExact($p);
                array_push($allPlayers, $player);
                if($player instanceof Player) {
                    $player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $this->restoreSkin($player);
                    $this->plugin->sendUIItems($player, "INF");
                    $p = $this->plugin->getPlayer($player);
                    $p->setId(false);
                    $p->setGamemode(false);
                    $this->plugin->getPlayer($player)->setTeam(false);
                    $this->updateTeamFormat($player);
                    $this->plugin->getKit()->rmKitUsed($player);
                    $player->setNameTag($player->getName());
                    $player->setSpawn($default);
                    $player->teleport($default);
                    $winner = $this->getWinner();
                    $this->plugin->getScoreboard()->removeScore($player);
                    $player->sendMessage($this->plugin->prefixTres . "The match has been concluded!");
                    $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
                    $player->addTitle("", $winner[1], 60, 80, 20);
                }
            }
        }
    }

    // pickTeam is rewritten to make all players survivors at first. Zombies are selected later.

    public function pickTeam(Player $player) {
        $team = "survivor";
        $this->addPlayerCount();
        $this->addTeamCount($team);
        $this->addPlayer($player);
        $this->plugin->getPlayer($player)->setTeam($team);
        $this->teamSelectionEvent($player, $team);
        $this->plugin->getScoreboard()->addScore($player, "§l§eINFECTED");
    }

    public function getWinner(): array {
        $zombie = $this->getTeamCount("zombie");
        $survivor = $this->getTeamCount("survivor");

        if($zombie == 0) {
            return ["survivor", "§l§aZOMBIES HAVE BEEN ELIMINATED!"];
        }

        if($survivor == 0) {
            return ["zombie", "§l§cEVERYONE HAS BECOME A ZOMBIE..."];
        }

        if($zombie == $survivor && $survivor !== 0) {
            return ["survivor", "§l§aSURVIVORS SURVIVED THE INFECTION"];
        }
        else {
            return ["draw", "§l§cNo one survived...\nIt's a draw"];
        }
    }


    public function activeGameHudEvent(Player $player) {
    }



    public function onCertainTime(int $timeUnit, int $time)
    {
        // TODO: Implement onCertainTime() method.
    }

    public function updateTeamFormat(Player $player, $team = false) {
        $p = $this->plugin->getPlayer($player);
        $format = $p->getChatFormat();

        if($team) {
            if($team == "zombie") {
                $team = "§8[§cZombie§8]§3";
                $newFormat = str_replace("#", $team, $format);
            }
            else {
                $team = "§8[§eSurvivor§8]§3";
                $newFormat = str_replace("#", $team, $format);
            }
        }
        else {
            $newFormat = str_replace($this->chatTags, "#", $format);
        }
        $p->setChatFormat($newFormat);
    }

    public function teamSelectionEvent(Player $player, $team) {
        $this->updateTeamFormat($player, $team);
        $subtitle = "noMessage";
        if($team == "zombie") {
            $zomb = $this->getPosition("spawn", "zombie");
            $player->setNameTag("§8[§cZombie§8] §f" . $player->getName());
            $player->setSpawn($zomb);
            $subtitle = "§l§cYou have become INFECTED!";
        }
        elseif($team == "survivor") {
            $surv = $this->getPosition("spawn", "survivor");
            $player->setNameTag("§8[§eSurvivor§8] §f" . $player->getName());
            $player->setSpawn($surv);
            $subtitle = "You are a " . $team . "!";
        }
        $player->addTitle("", $subtitle, 40,  70, 40);
        $player->getLevel()->addSound(new EndermanTeleportSound($player->getPosition()));
    }

    public function teleportQueueEvent()
    {
        // TODO: Implement teleportQueueEvent() method.
    }

    public function queuedPlayerEvent(Player $player) {
        $this->teleportQR($player);
        $player->sendMessage($this->plugin->prefix . "You've been queued for the match!");
    }

    public function teleportPlayer(Player $player) {
        $team = $this->plugin->getPlayer($player)->getTeam();

        if($team == "survivor") {
            $surv = $this->getPosition("spawn", "survivor");
            $player->teleport($surv);
        }
        if($team == "zombie") {
            $zomb = $this->getPosition("spawn", "zombie");
            $player->teleport($zomb);
        }
    }

    public function forcePlayerEvent(Player $player) {
        $team = $this->plugin->getPlayer($player)->getTeam();
        
        if($team == "survivor") {
            $surv = $this->getPosition("spawn", "survivor");
            $player->teleport($surv);
        }
        if($team == "zombie") {
            $zomb = $this->getPosition("spawn", "zombie");
            $player->teleport($zomb);
        }
    }

    public function onPlayerRespawnEvent(Player $player)
    {
        // TODO: Implement onPlayerRespawnEvent() method.
    }

    public function onPlayerDeathEvent(Player $player)
    {
        // TODO: Implement onPlayerDeathEvent() method.
    }

    public function onPlayerDamageEvent(Player $player)
    {
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
        $zombSpawn = $this->cfg->get("z-spawn");
        $this->addPosition(new Position($zombSpawn["pos-x"], $zombSpawn["pos-y"], $zombSpawn["pos-z"], $this->getLvl($zombSpawn["level"])), "spawn", "zombie");
        $survSpawn = $this->cfg->get("s-spawn");
        $this->addPosition(new Position($survSpawn["pos-x"], $survSpawn["pos-y"], $survSpawn["pos-z"], $this->getLvl($survSpawn["level"])), "spawn", "survivor");
    }

    public function insideSpawn(Vector3 $pos, string $levelName, string $team) : bool{
        $pos1 = $this->getPosition("pos1", $team);
        $pos2 = $this->getPosition("pos2", $team);
        return ((min($pos1->getX(), $pos2->getX()) <= $pos->getX()) && (max($pos1->getX(), $pos2->getX()) >= $pos->getX()) && (min($pos1->getY(), $pos2->getY()) <= $pos->getY()) && (max($pos1->getY(), $pos2->getY()) >= $pos->getY()) && (min($pos1->getZ(), $pos2->getZ()) <= $pos->getZ()) && (max($pos1->getZ(), $pos2->getZ()) >= $pos->getZ()) && ($pos1->getLevel()->getName() === $levelName));
    }

    public function startInfection(string $username) {
        if(!isset($this->preInfected[$username])) {
            $this->preInfected[$username] = 9;
        }
    }

    public function increaseInfection(string $username) {
        if(isset($this->preInfected[$username])) {
            $t = $this->preInfected[$username];
            unset($this->preInfected[$username]);
            $this->preInfected[$username] = $t - 1;
        }
    }

    public function getPreInfected(string $username) {
        if(isset($this->preInfected[$username])) {
            return $this->preInfected[$username];
        }
    }

    public function preInfected(string $username): bool {
        if(isset($this->preInfected[$username])) {
            return true;
        }
        else return false;
    }

    public function rmPreInfection(string $username) {
        if(isset($this->preInfected[$username])) {
            unset($this->preInfected[$username]);
        }
    }

    public function getAllPreInfected() {
        return $this->preInfected;
    }

    // PHP gd extension must be compiled.

    public function setZombieSkin(Player $player) {
        $image = imagecreatefrompng($this->plugin->getDataFolder() . "zombie.png");
        $data = '';
        for ($y = 0, $height = imagesy($image); $y < $height; $y++) {
            for ($x = 0, $width = imagesx($image); $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $data .= pack("c", ($color >> 16) & 0xFF) //red
                    . pack("c", ($color >> 8) & 0xFF) //green
                    . pack("c", $color & 0xFF) //blue
                    . pack("c", 255 - (($color & 0x7F000000) >> 23)); //alpha
            }
        }
        $this->saveSkin($player);
        $skin = new Skin("Standard_Custom", $data);
        $player->setSkin($skin); //Standard_Custom for alex
        $player->sendSkin();
    }

    public function turnZombie(Player $player) {
        $this->removeTeamCount("survivor");
        $this->addTeamCount("zombie");
        $survivorsLeft = $this->getTeamCount("survivor");
        if($survivorsLeft == 0) {
            $this->endMatch();
            return;
        }
        $this->plugin->getPlayer($player)->setTeam("zombie");
        $this->setZombieSkin($player);
        $this->teamSelectionEvent($player, "zombie");
    }

    public function saveSkin(Player $player) {
        $username = strtolower($player->getName());

        if(!isset($this->skinData[$username])) {
            $this->skinData[$username] = $player->getSkin();
        }
    }

    public function removeSkin(Player $player) {
        $username = strtolower($player->getName());

        if(isset($this->skinData[$username])) {
            unset($this->skinData[$username]);
        }
    }

    public function restoreSkin(Player $player) {
        $username = strtolower($player->getName());

        if(isset($this->skinData[$username])) {
            $skin = $this->skinData[$username];
            $player->setSkin($skin);
            $player->sendSkin();
            unset($this->skinData[$username]);
        }
    }
}