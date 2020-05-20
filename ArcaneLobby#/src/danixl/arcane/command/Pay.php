<?php
declare(strict_types=1);

namespace danixl\arcane\command;

use danixl\arcane\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Pay extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('pay', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.pay');
        $this->setDescription("Pay another player");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if(!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage("/pay <player> <amount>");
            return false;
        }
        else {
            if(!is_int($args[1])) {
                $sender->sendMessage("Please enter a correct integer.");
                return false;
            }
            if($args[1] < 1) {
                $sender->sendMessage("Payment amount needs to be more than 1 coin.");
                return false;
            }
        }
        $player = $this->plugin->getServer()->getPlayer($args[0]);
        if($player === $sender) {
            $sender->sendMessage("You can't pay yourself.");
            return false;
        }
        if(!$player instanceof Player) {
            $sender->sendMessage("Please specify a valid player.");
            return false;
        }
        if($args[1] > $this->plugin->getPlayer($sender)->getCoins()) {
            $sender->sendMessage("Not enough coins to pay" . $args[0] . ".");
            return false;
        }
        $this->plugin->getPlayer($player)->addCoins($args[1]);
        $this->plugin->getPlayer($sender)->takeCoins($args[1]);
        $username = $player->getName();
        $sender->sendMessage("Paid " . $username . " " . $args[1] . " coins successfully!");
        $player->sendMessage($sender->getName() . " has paid you " . $args[1] . " coins!");
        $player->sendMessage("Your coin balance now: " . $this->plugin->getPlayer($player)->getCoins());
        return true;
    }
}