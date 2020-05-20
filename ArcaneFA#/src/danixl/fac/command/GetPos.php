<?php

declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class GetPos extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('getpos', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.getpos');
        $this->setDescription("Output your current position with coords");

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
            $x = round($sender->x);
            $y = round($sender->y);
            $z = round($sender->z);
            $level = $sender->getLevel()->getName();
            $sender->sendMessage($this->plugin->prefix .  "§8- §ax§f: " . $x . ", §cy§f: " . $y . ", §9z§f: " . $z . ", §7level: " . $level);
        }
        else {
            if(strtolower($args[0]) == "block") {
                $username = strtolower($sender->getName());
                $this->plugin->admin[$username]["getpos"] = 0;
                $sender->sendMessage($this->plugin->prefix .  "Break a block to get the position coords of a block.");
                return true;
            }
            else {
                $sender->sendMessage($this->plugin->prefix .  "/getpos");
                $sender->sendMessage($this->plugin->prefix .  "/getpos block");
            }
        }
        return true;
	}
}