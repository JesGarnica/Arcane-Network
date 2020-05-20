<?php

declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Heal extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('heal', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.heal');
        $this->setDescription("Restore your health to 20.");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if($sender instanceof Player) {
            if($this->plugin->getPlayer($sender)->getHealUsage() >= 1) {
                $sender->setHealth(20);
                $sender->sendMessage($this->plugin->prefix .  "You have restored your health. -1 /heal usage.");
                $newHealUsage = $this->plugin->getPlayer($sender)->getHealUsage() - 1;
                $this->plugin->getPlayer($sender)->setHealUsage($newHealUsage);
                return true;
            }
            $sender->sendMessage($this->plugin->prefix .  "You can only use /heal twice during each spawn. /heal usages will restore at respawn.");
            return false;
        }
        $sender->sendMessage("Please run this command in-game.");
        return false;
    }
}