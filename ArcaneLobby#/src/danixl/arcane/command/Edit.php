<?php

declare(strict_types=1);

namespace danixl\arcane\command;

use danixl\arcane\Main;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Edit extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('edit', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.edit');
        $this->setDescription("Edit lobby");

    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        $username = strtolower($sender->getName());
        if(isset($this->plugin->edit[$username])) {
            unset($this->plugin->edit[$username]);
            $sender->sendMessage("Disabled edit mode.");
        }
        else {
            $this->plugin->edit[$username] = $username;
            $sender->sendMessage("Enabled edit mode.");
        }
        return true;
	}
}