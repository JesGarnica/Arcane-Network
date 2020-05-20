<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};


class Fly extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('fly', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.fly');
        $this->setDescription("Toggle flight");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        $this->plugin->getPlayer($sender)->toggleFlight();
        $sender->sendMessage($this->plugin->prefix .  "Enabled/disabled flight mode.");
        return true;
	}
}