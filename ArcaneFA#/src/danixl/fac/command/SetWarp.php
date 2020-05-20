<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class SetWarp extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('setwarp', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.warpmanage');
        $this->setDescription("Set Warp");
        $this->setAliases(["createwarp", "newwarp"]);

    }

    public function execute(CommandSender $sender, string $label, array $args): bool{
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/setwarp <warp>");
            return false;
        }
        $warp = strtolower($args[0]);
        $this->plugin->getWarp()->createWarp($sender, $warp);
        $sender->sendMessage($this->plugin->prefix . "Created warp: " . $warp);
        return true;
    }
}