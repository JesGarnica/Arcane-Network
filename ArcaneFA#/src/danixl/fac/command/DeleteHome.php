<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class DeleteHome extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('deletehome', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.delhome');
        $this->setDescription("Set home");

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
                $this->plugin->getPlayer($sender)->delHome("default");
                $sender->sendMessage($this->plugin->prefix . "Deleted your home!");
                return true;
            }
            $sender->sendMessage($this->plugin->prefix . "Default home doesn't exist. Create a home using /sethome.");
            return false;
        }
        else {
            $homeName = strtolower($args[0]);
            if($this->plugin->getPlayer($sender)->homeExists($homeName)) {
                $this->plugin->getPlayer($sender)->delHome($home);
                $sender->sendMessage($this->plugin->prefix . "Deleted your home!");
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos . $homeName . " home doesn't exist in your player data.");
            return false;
        }
    }
}