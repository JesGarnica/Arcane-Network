<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};


class DelWarp extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('delwarp', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.warpmanage');
        $this->setDescription("Delete warp");

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
            $sender->sendMessage("/delwarp <warp>");
            return false;
        }
        else {
            $warp = strtolower($args[0]);
            if($this->plugin->getWarp()->warpExists($warp)) {
                $this->plugin->getWarp()->delWarp($warp);
                $sender->sendMessage($this->plugin->prefix . "Deleted warp: " . $warp);
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos . "Warp doesn't exist.");
            return false;
        }
    }
}