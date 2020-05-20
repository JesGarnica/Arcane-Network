<?php
/**
 * A lot of the code is taken from official Slapper add-ons.
 */

namespace danixl\arcane\listeners;

use danixl\arcane\Main;
use danixl\arcane\utils\form\CustomForm;
use danixl\arcane\utils\form\SimpleForm;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerMoveEvent;

use pocketmine\math\Vector2;

use pocketmine\network\mcpe\protocol\MoveEntityAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use slapper\entities\SlapperHuman;
use slapper\events\SlapperHitEvent;

use pocketmine\Player;

class SlapperListener implements Listener {

    private $plugin;

    private $fullNames = ["Capture the Flag" => "CTF", "Infected" => "INF"];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerMove(PlayerMoveEvent $ev) {
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();
        if($from->distance($to) < 0.1) {
            return;
        }
        $maxDistance = 10;
        foreach ($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $player) as $e) {
            if($e instanceof Player) {
                continue;
            }
            if(substr($e->getSaveId(), 0, 7) !== "Slapper") {
                continue;
            }
            switch ($e->getSaveId()) {
                case "SlapperFallingSand":
                case "SlapperMinecart":
                case "SlapperBoat":
                case "SlapperPrimedTNT":
                case "SlapperShulker":
                    continue 2;
            }
            $xdiff = $player->x - $e->x;
            $zdiff = $player->z - $e->z;
            $angle = atan2($zdiff, $xdiff);
            $yaw = (($angle * 180) / M_PI) - 90;
            $ydiff = $player->y - $e->y;
            $v = new Vector2($e->x, $e->z);
            $dist = $v->distance($player->x, $player->z);
            $angle = atan2($dist, $ydiff);
            $pitch = (($angle * 180) / M_PI) - 90;
            if($e->getSaveId() === "SlapperHuman") {
                $pk = new MovePlayerPacket();
                $pk->entityRuntimeId = $e->getId();
                $pk->position = $e->asVector3()->add(0, $e->getEyeHeight(), 0);
                $pk->yaw = $yaw;
                $pk->pitch = $pitch;
                $pk->headYaw = $yaw;
                $pk->onGround = $e->onGround;
            } else {
                $pk = new MoveEntityAbsolutePacket();
                $pk->entityRuntimeId = $e->getId();
                $pk->position = $e->asVector3();
                $pk->xRot = $pitch;
                $pk->yRot = $yaw;
                $pk->zRot = $yaw;
            }
            $player->dataPacket($pk);
        }
    }

    public function onSlapperHit(SlapperHitEvent $ev){
        $entity = $ev->getEntity();
        if(!$entity instanceof SlapperHuman){
            return;
        }
        $player = $ev->getDamager();
        $username = strtolower($player->getName());

        if(isset($this->plugin->edit[$username])) {
            $this->plugin->getServerStats()->getServerUI()->createServerUI($player);
        }
        else {
            $name = TextFormat::clean($entity->getName());
            if(isset($this->fullNames[$name])) {
                $name = $this->fullNames[$name];
            }
            $this->plugin->getServerStats()->getServerUI()->selectSubUI($player, $name);
        }
    }
}