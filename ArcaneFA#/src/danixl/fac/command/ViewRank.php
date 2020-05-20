<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;
use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class ViewRank extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('viewrank', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.viewrank');
        $this->setDescription("View ranks");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(empty($args[0])) {
            if($sender instanceof Player) {
                $primaryRank = $this->plugin->getPlayer($sender)->playerRank("primary");
                $secondaryRank = $this->plugin->getPlayer($sender)->playerRank("secondary");
                $sender->sendMessage($this->plugin->prefix .  "-- §l§6Your Ranks §r--");
                $sender->sendMessage($this->plugin->prefix .  "§l§cPrimary§r§8: §f" . $primaryRank);
                $sender->sendMessage($this->plugin->prefix .  "§l§bSecondary§r§8: §f" . $secondaryRank);
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos .  "Please select a player. /viewrank <player>");
            return false;
        }
        else {
            $player = $this->plugin->getServer()->getPlayer($args[0]);
            if($player instanceof Player) {
                $primaryRank = $this->plugin->getPlayer($player)->playerRank("primary");
                $secondaryRank = $this->plugin->getPlayer($player)->playerRank("secondary");
                $sender->sendMessage($this->plugin->prefix .  "-- §l§6" . $player->getName() . "'s Ranks §r--");
                $sender->sendMessage($this->plugin->prefix .  "§l§cPrimary§r§8: §f" . $primaryRank);
                $sender->sendMessage($this->plugin->prefix .  "§l§bSecondary§r§8: §f" . $secondaryRank);
                return true;
            }
            $sender->sendMessage($this->plugin->prefixDos .  "Player doesn't exist or is not online.");
            return false;
        }
	}
}