<?php

declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Kit extends PluginCommand {

    private $plugin, $k;

    public function __construct(Main $plugin) {
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
                $p = $this->plugin->getPlayer($sender);
                if($p->isKitUsed($kit)) {
                    if($p->isKitCooling($kit)) {
                        $seconds = $p->getKitUnit($kit);
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                        $output = "(" . $hours . " hours, " . $minutes . " minutes, " . $seconds . " seconds)";
                        $sender->sendMessage($this->plugin->prefixDos . "Please wait " . $output . " until you can use this kit again.");
                        return false;
                    }
                    $sender->sendMessage($this->plugin->prefixDos . "This kit is not available at the moment. Please wait until the kit's cool-down is done.");
                    return false;
                }
                $this->k->giveKit($sender, $kit);
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos . "You don't have permission to use the " . $kit . " kit.");
            return false;
        }
        $sender->sendMessage($this->plugin->prefixDos . "Kit doesn't exist. Note, kit names are case sensitive.");
        return false;
    }
}