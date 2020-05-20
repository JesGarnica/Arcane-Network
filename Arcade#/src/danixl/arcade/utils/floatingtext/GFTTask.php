<?php

namespace danixl\arcade\utils\floatingtext;

use danixl\arcade\Arcade;

use pocketmine\scheduler\Task;

class GFTTask extends Task {

    private $plugin;

     public function __construct(GFTManager $plugin) {
         $this->plugin = $plugin;
     }

     public function onRun(int $tick) {
         $this->plugin->updateFloatingTextParticles();
         if(!empty($this->plugin->getSpawnedTo())) {
             foreach($this->plugin->getSpawnedTo() as $p => $id) {
                 $player = Arcade::getAPI()->getServer()->getPlayerExact($p);
                 $this->plugin->despawnFloatingTextParticleTo($player);
                 $this->plugin->spawnFloatingTextParticleTo($player, $id);
             }
         }
     }
}