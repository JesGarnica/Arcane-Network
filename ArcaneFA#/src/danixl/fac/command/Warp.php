<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};


class Warp extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('warp', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.warp');
        $this->setDescription("Teleport to a warp");

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
            $sender->sendMessage("/warp <warp>");
            return false;
        }
        else {
            $warp = strtolower($args[0]);
            if($this->plugin->getWarp()->warpExists($warp)) {
                if($this->plugin->getPlayer($sender)->isLogged()) {
                    $sender->sendMessage($this->plugin->prefixDos . "Warping is not allowed while in combat.");
                    return false;
                }
                switch($warp) {

                    case ($warp == "cp" || $warp == "calapooia" || $warp == "survival" || $warp == "wild"):
                        $title = "~ §l§eWild §r~";
                        $subtitle = "§oIt's the survival of the fittest.";
                        break;

                    case ($warp == "dz" || $warp == "deadzone" || $warp == "pvp"):
                        $title = "~ §l§cDeadZone §r~";
                        $subtitle = "Do or die!";
                        $warp = "deadzone";
                        $pos = $this->plugin->getWarp()->getWarpPosition("deadzone");
                        $sender->setSpawn($pos);
                        break;

                    case ($warp == "forest" || $warp == "forests" || $warp == "fh"):
                        $title = "~ §l§2Forest §aHills§r~";
                        $subtitle = "Beware of the arrows!";
                        $warp = "forest";
                        $pos = $this->plugin->getWarp()->getWarpPosition("forest");
                        $sender->setSpawn($pos);
                        break;

                    case "shop":
                        $title = "§l§aShop";
                        $subtitle = "§6Buy or sell tools, armor & more!";
                        break;

                    default:
                        $title = "§l" . $warp;
                        $subtitle = "§oWelcome to " . $warp;
                }
                $this->plugin->getWarp()->teleportWarp($sender, $warp);
                $sender->addTitle($title, $subtitle, $fadeIn = 20, $duration = 90, $fadeOut = 20);
                $sender->sendMessage($this->plugin->prefix . "Teleported to " . $warp . ".");
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos . "Warp doesn't exist.");
            return false;
        }
	}
}