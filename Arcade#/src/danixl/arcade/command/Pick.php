<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Pick extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('pick', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.pick');
        $this->setDescription("Add a player to a match");

    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage($this->plugin->prefix .  "/pick <player> <id>");
            return false;
        }
        $id = $args[1];
        $player = $this->plugin->getServer()->getPlayer($args[0]);
        if($player instanceof Player) {
            if($game = $this->plugin->getManager()->getGame($id)) {
                if($game->arenaExists() && $game->getStatus() == 0) {
                    if(!$this->plugin->getPlayer($player)->hasId()) {
                        $game->queuePlayer($player);
                        $sender->sendMessage($this->plugin->prefix .  $player->getName() . " has been queued!");
                        return true;
                    }
                    $sender->sendMessage($this->plugin->prefixDos .  "It appears that " . $player->getName() . " is in a match.");
                    return false;
                }
                $sender->sendMessage($this->plugin->prefixDos .  "There appears to be an error. Match is not available at the moment.");
                return false;
            }
            $sender->sendMessage($this->plugin->prefix .  "Game instance was not found with the ID: " . $id);
            return false;
        }
        $sender->sendMessage($this->plugin->prefixDos .  $args[0] . " doesn't exist.");
        return false;
	}
}