<?php
declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\item\Item;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;
use pocketmine\utils\TextFormat;

class Lobby extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('lobby', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.lobby');
        $this->setDescription("Return to lobby");
        $this->setAliases(["hub", "spawn"]);

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
            $p = $this->plugin->getPlayer($sender);
            if($p->hasId()) {
                $p->purgeGameData();
            }
            $this->plugin->getKit()->rmKitUsed($sender);
            $sender->transfer("play.arcn.us", 19132, "Transferring to hub.");
            return true;
        }
	}
}