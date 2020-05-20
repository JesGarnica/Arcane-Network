<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class TPA extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('tpa', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.tpa');
        $this->setDescription("Teleport to friend");

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
            $sender->sendMessage("/tpa <player>");
            return false;
        }
        $target = $this->server->getPlayer($args[0]);
        if($sender == $target) {
            $sender->sendMessage($this->plugin->prefixDos . "You can't send teleport requests to yourself.");
            return false;
        }
        if($target instanceof Player) {
            if($this->plugin->getPlayer($sender)->isLogged()) {
                $sender->sendMessage($this->plugin->prefixDos . "Teleport requests are not allowed while in combat.");
                return false;
            }
            $this->plugin->getPlayer($sender)->requestTP($target);
            return true;
        }
        $sender->sendMessage($this->plugin->prefixDos . $args[0] . " doesn't exist or isn't online.");
        return false;
    }
}