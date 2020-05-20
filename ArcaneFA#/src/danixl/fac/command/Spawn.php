<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Spawn extends PluginCommand {

    private $plugin, $server;

    public function __construct(Main $plugin) {
        parent::__construct('spawn', $plugin);
        $this->plugin = $plugin;
        $this->server = $plugin->getServer();
        $this->setPermission('arc.cmd.spawn');
        $this->setDescription("Teleport to server spawn");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
        }
        else {
            $default = $this->server->getDefaultLevel()->getSafeSpawn();
            $sender->setSpawn($default);
            $sender->teleport($default);
            $sender->sendMessage($this->plugin->prefix . "Teleported to spawn!");
        }
        return true;
    }
}