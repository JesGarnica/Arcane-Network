<?php

declare(strict_types=1);

namespace danixl\arcane\command;

use danixl\arcane\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class SendCoins extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('sendcoins', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.sendcoins');
        $this->setDescription("Send coins to another player");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage("/sendcoins <player> <amount>");
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
        $this->plugin->getPlayer($player)->addCoins($args[1]);
        $username = $player->getName();
        $sender->sendMessage("Coins sent to " . $username . ".");
        $player->sendMessage("You have received " . $args[1] . " coins.");
        $player->sendMessage("Your coins balance now: " . $this->plugin->getPlayer($player)->getCoins());
        return true;
	}
}