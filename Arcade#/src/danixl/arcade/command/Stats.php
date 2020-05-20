<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Stats extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('stats', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.stats');
        $this->setDescription("View stats");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(empty($args[0])) {
            if(!$sender instanceof Player) {
                $sender->sendMessage("Please run this command in-game.");
                return false;
            }
            $this->plugin->createPlayerUI($sender);
            return true;
        }
        else {
            if(isset($args[0])) {
                $player = $this->plugin->getServer()->getPlayer($args[0]);
                if($player === $sender) {
                    $sender->sendMessage($this->plugin->prefixDos .  "You can't select yourself.");
                    return false;
                }
                if($player instanceof Player) {
                    $this->plugin->createPlayerUI($player);
                }
                $sender->sendMessage($this->plugin->prefixDos .  "Please specify a valid player.");
                return false;
            }
        }
	}
}