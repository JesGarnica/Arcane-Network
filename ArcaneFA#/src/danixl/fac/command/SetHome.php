<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class SetHome extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('sethome', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.sethome');
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
            $this->plugin->getPlayer($sender)->createHome("default");
            $sender->sendMessage($this->plugin->prefix . "Set your default home!");
            return true;
        }
        if($sender->hasPermission("fac.unlimitedhomes")) {
            $homeName = strtolower($args[0]);
            $this->plugin->getPlayer($sender)->createHome($homeName);
            $sender->sendMessage($this->plugin->prefix . "Set your home, " . $homeName . "!");
        }
        else {
            $sender->sendMessage($this->plugin->prefixDos . "You don't have permission to set named homes.");
        }
        return true;
    }
}