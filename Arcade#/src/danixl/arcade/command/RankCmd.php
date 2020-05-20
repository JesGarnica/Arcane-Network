<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class RankCmd extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('rank', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.rank');
        $this->setDescription("Change ranks");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1]) || !isset($args[2])) {
            $sender->sendMessage("Please enter the correct parameters.\nrank <class> <rank> <player>");
            return false;
        }
        $player = $this->plugin->getServer()->getPlayer($args[2]);
        if(!$player instanceof Player) {
            $sender->sendMessage($this->plugin->prefixDos .  "Please specify a valid player.");
            return false;
        }
        $classes = ["primary", "secondary"];
        if(!in_array(strtolower($args[0]), $classes)) {
            $sender->sendMessage($this->plugin->prefixDos .  "Rank class doesn't exist.");
            return false;
        }
        if($this->plugin->getRank()->isRank(strtolower($args[1]))) {
            $inputedClass = strtolower($args[0]);
            $actualClass = strtolower($this->plugin->getRank()->getRankClass(strtolower($args[1])));
            if($inputedClass !== $actualClass) {
                $sender->sendMessage($this->plugin->prefix .  "Please enter the correct class for this rank.");
                return false;
            }
            $username = $player->getName();
            $this->plugin->getPlayer($player)->setRank($args[0], $args[1], true);
            $this->plugin->getRank()->setRankPermissions($player);
            $player->sendMessage($this->plugin->prefix .  "Your " . strtolower($args[0]) . " rank is now " . strtolower($args[1]) . "!");
            $sender->sendMessage($this->plugin->prefix .  "Successfully ranked " . $username. "!");
            return true;
        }
        $sender->sendMessage($this->plugin->prefixDos .  "Rank doesn't exist.");
        return false;
	}
}