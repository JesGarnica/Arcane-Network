<?php

declare(strict_types=1);

namespace danixl\arcade\game;

use pocketmine\scheduler\Task;

use danixl\arcade\Arcade;
use danixl\arcade\game\Game;

use pocketmine\Player;
use pocketmine\utils\TextFormat;

use pocketmine\level\sound\LaunchSound;

final class PreGameTask extends Task {

    private $game, $plugin, $server;

    public function __construct(Game $game) {
        $this->game = $game;
        $this->plugin = $this->game->plugin;
        $this->server = $this->plugin->getServer();
    }

    private function onWait() {
        $current = $this->game->getTime() - 1;
        $this->game->setTime($current);
        $seconds = $this->game->getTime();
        $queue = $this->game->getQueues();

        if(count($queue) <= 1) {
            $this->game->purgeGameTask();
            $this->game->setTaskId(false);
            $this->game->setTime(0);
        }

        if($seconds == 0) {
            $this->game->purgeGameTask();
            $this->game->setTime(0);
            $this->game->transferMatchData();
            return true;
        }

        switch($seconds) {
            case 60:
            $this->game->broadcastMSG($this->plugin->prefix . " 1 minute until the match commences...");
            break;

            case 30:
            $this->game->broadcastMSG($this->plugin->prefix . " 30 seconds until the match commences...");
            break;

            case 20:
            $this->game->broadcastMSG($this->plugin->prefix . " 20 seconds until the match commences...");
            break;

            case ($seconds <= 10):
                $this->game->broadcastMSG(TextFormat::BOLD . TextFormat::YELLOW . $seconds, 3);
                break;

        }
    }    
    
    public function onRun(int $currentTick): void{
        if(!$this->game->getStatus() == 1) {
            $this->onWait();  
        }
    }
}