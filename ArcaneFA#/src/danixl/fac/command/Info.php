<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};


class Info extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('info', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.vinfo');
        $this->setDescription("View ArcaneFA info");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        $fullName = $this->plugin->getDescription()->getFullName();
        $sender->sendMessage("§l§2This server is running §f" . $fullName);
        $sender->sendMessage("§7Developed by @danielgxa (Daniel)");
        return true;
    }
}