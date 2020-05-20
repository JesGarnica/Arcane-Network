<?php

declare(strict_types=1);

namespace danixl\arcane\command;

use danixl\arcane\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class RankCmd extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
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
            $sender->sendMessage("Please specify a valid player.");
            return false;
        }
        $classes = ["primary", "secondary"];
        if(!in_array(strtolower($args[0]), $classes)) {
            $sender->sendMessage("Rank class doesn't exist.");
            return false;
        }
        if($this->plugin->getRank()->isRank(strtolower($args[1]))) {
            $inputedClass = strtolower($args[0]);
            $actualClass = strtolower($this->plugin->getRank()->getRankClass(strtolower($args[1])));
            if($inputedClass !== $actualClass) {
                $sender->sendMessage("Please enter the correct class for this rank.");
                return false;
            }
            $username = $player->getName();
            $this->plugin->getPlayer($player)->setRank($args[0], $args[1], true);
            $this->plugin->getRank()->setRankPermissions($player);
            $player->sendMessage("Your " . strtolower($args[0]) . " rank is now " . strtolower($args[1]) . "!");
            $sender->sendMessage("Successfully ranked " . $username. "!");
            return true;
        }
        $sender->sendMessage("Rank doesn't exist.");
        return false;
	}
}