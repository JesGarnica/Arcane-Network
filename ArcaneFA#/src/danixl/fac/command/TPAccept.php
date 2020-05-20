<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class TPAccept extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('tpaccept', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.tpaccept');
        $this->setDescription("Accept teleport request");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if($this->plugin->getPlayer($sender)->isLogged()) {
            $sender->sendMessage($this->plugin->prefixDos . "Teleport requests are not allowed while in combat.");
            return false;
        }
        $this->plugin->getPlayer($sender)->respondTP("accept");
        return true;
	}
}