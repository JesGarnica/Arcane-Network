<?php

declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class FT extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('ft', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.ft');
        $this->setDescription("Floating text manager");
    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        $this->plugin->getFTManager()->createFTManagerUI($sender);
        return true;
	}
}