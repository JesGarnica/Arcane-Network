<?php

declare(strict_types=1);

namespace danixl\arcade\task;

use pocketmine\scheduler\Task;

use danixl\arcade\Arcade;

use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class SignRefreshTask extends Task {

    private $plugin;

    public function __construct(Arcade $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        foreach($this->plugin->getManager()->signPositions as $k => $signData) {
            $sign = $signData['pos']->getLevel()->getTile($signData['pos']);
            if($sign instanceof Sign) {
                if($this->plugin->getManager()->gameExists($k)) {
                    $game = $this->plugin->getManager()->getGame($k);
                    switch(TextFormat::clean($game->gameName())) {
                        case "CTF":
                            $data[0] = "§l§cC§9T§7F";
                            $data[1] = "§l§d" . ucfirst($game->arenaName());
                            if($game->getStatus() == 1) {
                                $data[2] = "[§6In§7-§6Match§0]";
                                $data[3] = "§f" . $game->getPlayerCount() . "§0/§f12";
                                $sign->setText($data[0], $data[1], $data[2], $data[3]);
                            }
                            else {
                                $data[2] = "[§aQueueing§0]";
                                $data[3] = "§f" . count($game->getQueues()) . "§0/§f12";
                                $sign->setText($data[0], $data[1], $data[2], $data[3]);
                            }
                            break;

                        case "INF":
                            $data[0] = "§l§cINF";
                            $data[1] = "§l§d" . ucfirst($game->arenaName());
                            if($game->getStatus() == 1) {
                                $data[2] = "[§6In§7-§6Match§0]";
                                $data[3] = "§f" . $game->getPlayerCount() . "§0/§f12";
                                $sign->setText($data[0], $data[1], $data[2], $data[3]);
                            }
                            else {
                                $data[2] = "[§aQueueing§0]";
                                $data[3] = "§f" . count($game->getQueues()) . "§0/§f12";
                                $sign->setText($data[0], $data[1], $data[2], $data[3]);
                            }
                            break;
                    }
                    return true;
                }
                $this->plugin->getLogger()->info("Game instance: "  . $k . " doesn't exist.");
            }
            else {
                $this->plugin->getLogger()->info("Sign data exists for ID: " . $k . ", although sign tile doesn't exist.");
            }
        }
    }
}