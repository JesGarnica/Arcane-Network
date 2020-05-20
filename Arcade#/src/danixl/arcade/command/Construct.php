<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Construct extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('construct', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.construct');
        $this->setDescription("Create a game instance");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1]) || !isset($args[2])) {
            $sender->sendMessage("/construct <id> <arena> <gameName>");
            return true;
        }

        $id = $args[0];
        if(strlen($id) !== 2) {
            $sender->sendMessage($this->plugin->prefixDos .  "ID can only be 2 characters.");
            return false;
        }
        if(is_numeric($id)) {
            $sender->sendMessage($this->plugin->prefixDos .  "ID must contain at least one letter. Ex: 1X");
            return false;
        }
        $arena = $args[1];
        $game = $args[2];
        $this->plugin->getManager()->createGame($id, $arena, $game);
        return true;
	}
}