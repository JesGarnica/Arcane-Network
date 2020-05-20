<?php

declare(strict_types=1);

namespace danixl\arcade\game\custom\ctf;

use pocketmine\level\particle\BubbleParticle;


use pocketmine\scheduler\Task;

use danixl\arcade\Arcade;

class HeldFlagTask extends Task {

    private $plugin, $server, $ctf;

     public function __construct(CTF $game) {
         $this->ctf = $game;
         $this->plugin = Arcade::getAPI();
         $this->server = Arcade::getAPI()->getServer();
     }


     public function onRun(int $currentTick): void {
         foreach($this->ctf->getAllFlagData() as $team => $u) {
             $player = $this->server->getPlayerExact($u);
             $p = $this->plugin->getPlayer($player);
             if($p->hasId() && $p->hasTeam()) {
                 $t = $p->getTeam();
                 if($t !== $team) {
                     $player->getLevel()->addParticle(new BubbleParticle($player->getPosition()));
                 }
             }
         }
     }
}