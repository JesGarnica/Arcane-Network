<?php

declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class SetCoins extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('setcoins', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.setcoins');
        $this->setDescription("Set a player's coins");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage("/setcoins <player> <amount>");
            return false;
        }
        if(!is_numeric($args[1])) {
            $sender->sendMessage("Please enter a correct integer.");
            return false;
        }
        $player = $this->plugin->getServer()->getPlayer($args[0]);
        if(!$player instanceof Player) {
            $sender->sendMessage("Please specify a valid player.");
            return false;
        }
        $this->plugin->getPlayer($player)->setCoins($args[1]);
        $username = $player->getName();
        $sender->sendMessage("Coins set to " . $username . ".");
        $player->sendMessage("Your coins have been set to " . $args[1] . ".");
        $player->sendMessage("Your coins balance now: " . $this->plugin->getPlayer($player)->getCoins());
        return true;
	}
}