<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\item\Item;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Lobby extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('lobby', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.lobby');
        $this->setDescription("Return to lobby");
        $this->setAliases(["hub"]);

    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        else {
            $sender->transfer("play.arcn.us", 19132, "Transferring to hub.");
            return true;
        }
	}
}