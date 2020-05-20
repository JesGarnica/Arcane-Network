<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Kit extends PluginCommand {

    private $plugin, $k;

    public function __construct(Arcade $plugin) {
        parent::__construct('kit', $plugin);
        $this->plugin = $plugin;
        $this->k = $plugin->getKit();
        $this->setPermission('arc.cmd.kit');
        $this->setDescription("Receive a kit");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if(empty($args[0])) {
            $sender->sendMessage("/kit <kit>");
            return false;
        }
        $kit = $args[0];
        if($this->k->isKit($kit)) {
            if($sender->hasPermission("arc.kit." . strtolower($kit))) {
                $this->k->giveKit($sender, $kit);
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos .  "You don't have permission to use the " . $kit . " kit.");
            return false;
        }
        $sender->sendMessage($this->plugin->prefixDos .  "Kit doesn't exist. Note, kit names are case sensitive.");
        return false;
	}
}