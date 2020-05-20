<?php

declare(strict_types=1);

namespace danixl\arcade\task;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\PopSound;
use pocketmine\scheduler\Task;

use danixl\arcade\Arcade;

use pocketmine\Player;

class HudTask extends Task {

    private $plugin, $server;

    public function __construct(Arcade $plugin) {
         $this->plugin = $plugin;
         $this->server = $this->plugin->getServer();
     }

    public function onRun(int $currentTick) {
        foreach($this->server->getOnlinePlayers() as $p){
            if($this->plugin->getPlayer($p)->hasId()) {
                $id = $this->plugin->getPlayer($p)->getId();
                if($this->plugin->getManager()->gameExists($id)) {
                    $game = $this->plugin->getManager()->getGame($id);
                    switch($game->getStatus()) {
                        case 0:
                            $queue = $game->getQueues();
                            if($p instanceof Player) {
                                if(count($queue) < 2) { // CHANGE TO 4
                                    $p->getLevel()->addSound(new BlazeShootSound($p->getPosition()));
                                    $p->sendPopup("§8- §l§6Waiting for more players to start match.");
                                }
                                else {
                                    $p->sendPopup("§8(§c" . count($queue) . "§8/§c12§8)");
                                }
                            }
                            break;

                        case 1:
                            $game->activeGameHudEvent($p);
                            break;
                    }
                }
            }
        }
    }
}