<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use danixl\arcade\Arcade;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class GFT extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('gft', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.gft');
        $this->setDescription("Game Floating text manager");
    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        $this->plugin->getGFTManager()->createFTManagerUI($sender);
        return true;
	}
}