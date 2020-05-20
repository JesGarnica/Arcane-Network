<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Home extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('home', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.home');
        $this->setDescription("Teleport to home");

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
            if($this->plugin->getPlayer($sender)->homeExists("default")) {
                $this->plugin->getPlayer($sender)->teleportHome("default");
                $sender->sendMessage($this->plugin->prefix . "Teleported to your home!");
                return true;
            }
            $sender->sendMessage($this->plugin->prefix . "Default home not found. Create a home using /sethome");
            return false;
        }
        else {
            $homeName = strtolower($args[0]);
            if($this->plugin->getPlayer($sender)->homeExists($homeName)) {
                if($this->plugin->getPlayer($sender)->isLogged()) {
                    $sender->sendMessage($this->plugin->prefixDos . "Home teleportation is not allowed while in combat.");
                    return false;
                }
                $this->plugin->getPlayer($sender)->teleportHome($homeName);
                $sender->sendMessage($this->plugin->prefix . "Teleported to your home, " . $homeName . "!");
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos . $homeName . " home doesn't exist in your player data.");
            return false;
        }
	}
}