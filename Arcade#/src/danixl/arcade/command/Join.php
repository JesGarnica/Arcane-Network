<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Join extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('join', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.join');
        $this->setDescription("Queue yourself in a match");

    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if(!isset($args[0])) {
            $sender->sendMessage($this->plugin->prefixDos .  "Please enter a Game ID");
            return false;
        }
        if($this->plugin->getManager()->gameExists($args[0])) {
            $game = $this->plugin->getManager()->getGame($args[0]);
            if($game->arenaExists()) {
                if($this->plugin->getPlayer($sender)->hasId()) {
                    $sender->sendMessage($this->plugin->prefixDos .  "You are currently in a match. Use /lobby to leave match.");
                    return false;
                }
                if($game->getStatus() == 1) {
                    if($sender->hasPermission("arc.premiumjoin")) {
                        $game->forcePlayer($sender);
                        $sender->sendMessage($this->plugin->prefix .  "You have joined an active match! Enjoy the match!");
                        return true;
                    }
                    $sender->sendMessage($this->plugin->prefixDos .  "Match unavailable due to arena being in-match.");
                    return false;
                }
                $game->queuePlayer($sender);
                $sender->sendMessage($this->plugin->prefix .  "You have been queued for [ID: " . $game->gameId() . "]\non arena: " . $game->arenaName());
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos .  "Arena data was not found.");
            return false;
        }
        $sender->sendMessage($this->plugin->prefixDos .  "Game instance doesn't exist.");
        return false;
	}
}