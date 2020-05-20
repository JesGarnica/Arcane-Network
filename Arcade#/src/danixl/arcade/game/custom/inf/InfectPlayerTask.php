<?php

declare(strict_types=1);

namespace danixl\arcade\game\custom\inf;

use danixl\arcade\Arcade;

use pocketmine\scheduler\Task;

class InfectPlayerTask extends Task {

    private $plugin, $server, $game;

     public function __construct(Infected $game) {
         $this->plugin = Arcade::getAPI();
         $this->game = $game;
         $this->server = $this->plugin->getServer();
     }
     
     public function onRun(int $tick) {
         if($this->game->getPlayerCount() <= 2) {
             $players = $this->game->getPlayers();
             $randPlayer = array_rand($players, 1);
             $player = $this->server->getPlayerExact($randPlayer);
             $this->game->turnZombie($player);
         }
         else {
             $players = $this->game->getPlayers();
             $randPlayer = array_rand($players, 2);
             $player = $this->server->getPlayerExact($randPlayer[0]);
             $player2 = $this->server->getPlayerExact($randPlayer[1]);
             $this->game->turnZombie($player);
             $this->game->turnZombie($player2);
         }
         $this->getHandler()->cancel();
     }
}