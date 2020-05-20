<?php

declare(strict_types=1);

namespace danixl\arcade\game;

use pocketmine\scheduler\Task;

use pocketmine\Server;

use pocketmine\Player;

final class GameTask extends Task {

    private $game, $plugin, $server, $prefix, $timeUnit;
    
    public function __construct(Game $game, int $timeUnit) {
        $this->game = $game;
        $this->plugin = $this->game->plugin;
        $this->server = $this->plugin->getServer();
        $this->timeUnit = $timeUnit;
    }

    private function matchMinutes() {
        $current = $this->game->getTime() - 1;
        $this->game->setTime($current);
        $minutes = $this->game->getTime();

        $this->game->onCertainTime($this->timeUnit, $this->game->getTime());

        if($minutes == 1) {
            $this->game->broadcastMSG($this->plugin->prefixDos . " 1 minute until the match ends...");
            $this->plugin->getScheduler()->cancelTask($this->game->getTaskId());
            $this->game->createGameTask("GameTask", 1);
            $this->game->setTime(61);
            //$this->getHandler()->setNextRun(20);
            return true;
        }
        if($minutes <= 8) {
            $this->game->broadcastMSG($this->plugin->prefixTres . " " .  $minutes . " minutes until the match ends.");
        }
    }

    private function matchSeconds() {
        $current = $this->game->getTime() - 1;
        $this->game->setTime($current);
        $seconds = $this->game->getTime();

        if($seconds == 1) {
            $this->game->broadcastMSG($this->plugin->prefixDos . " 1 second until match ends...");
            $this->game->purgeGameTask();
            $this->game->setTaskId(false);
            $this->game->endMatch();
            return true;
        }
        if($seconds >= 5) {
            $this->game->onCertainTime($this->timeUnit, $this->game->getTime());
        }
        if($seconds == 45) {
            $this->game->broadcastMSG($this->plugin->prefixTres . " " .  $seconds . " seconds until match ends.", 1);
            return true;
        }
        if($seconds <= 30) {
            $this->game->broadcastMSG($this->plugin->prefixDos . " " .  $seconds . " seconds until match ends.", 1);
        }
    }     

    public function onRun(int $currentTick) {
        if($this->game->getStatus() == 1) {
            if(count($this->game->getPlayers()) <= 1) {
                $this->game->purgeGameTask();
                $this->game->setTaskId(false);
                $this->game->endMatch();
                return true;
            }

            switch($this->timeUnit) {
                case 0:
                $this->matchMinutes();
                break;

                case 1:
                $this->matchSeconds();
                break;

                default:
                $this->server->getLogger()->warning("Invalid time unit used for Game Timer...");
            }
        }
    }
}