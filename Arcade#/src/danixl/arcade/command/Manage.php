<?php

declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Manage extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('manage', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.manage');
        $this->setDescription("Manage arena points");

    }

	public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->prefix .  "Please run this command in-game.");
            return false;
        }
        if(!isset($args[0])) {
            $sender->sendMessage($this->plugin->prefix .  "Please enter an arena.\n/manage <arena> <gamemode>");
            $sender->sendMessage($this->plugin->prefix .  "Enter in other parameters if required by the selected game.");
            return false;
        }
        if(isset($args[1])) {
            $arena = strtolower($args[0]);
            $gm = strtoupper($args[1]);
            if($gm == "CTF") {
                if(isset($args[2]) && isset($args[3])) {
                    $team = strtolower($args[2]);
                    $username = strtolower($sender->getName());
                    if($team == "blue" || $team == "red") {
                        $option = strtolower($args[3]);
                        if($option == "spawn" || $option == "flag" || $option == "return") {
                            $this->plugin->admin[$username]["arena"] = [$arena, "CTF"];
                            $this->plugin->admin[$username][$option] = $team;
                            $sender->sendMessage($this->plugin->prefix .  "Break a block to set " . $args[0] . " " . $team . "'s " . $option . " point.");
                            return true;
                        }
                        $sender->sendMessage($this->plugin->prefix .  "Select a valid option: flag, return, or spawn");
                        return false;
                    }
                    $sender->sendMessage($this->plugin->prefix .  "Select a valid team.");
                    return false;
                }
                $sender->sendMessage($this->plugin->prefix .  "Please enter all parameters for CTF.");
                $sender->sendMessage($this->plugin->prefix .  "CTF - /manage <arena> CTF <team> [spawn|flag|return]");
                return false;
            }
            if($gm == "INF") {
                if(isset($args[2]) && isset($args[3])) {
                    $team = strtolower($args[2]);
                    $username = strtolower($sender->getName());
                    if($team == "zomb" || $team == "surv") {
                        $option = strtolower($args[3]);
                        if($option == "spawn" || $option == "pos1" || $option == "pos2") {
                            $this->plugin->admin[$username]["arena"] = [$arena, "INF"];
                            $this->plugin->admin[$username][$option] = $team;
                            $sender->sendMessage($this->plugin->prefix .  "Break a block to set " . $team . "'s " . $option .  " point.");
                            return true;
                        }
                        $sender->sendMessage($this->plugin->prefix .  "/manage <arena> INF <team> spawn");
                        return false;
                    }
                    $sender->sendMessage($this->plugin->prefixDos .  "Select a valid team. (Zomb or Surv)");
                    return false;
                }
                $sender->sendMessage($this->plugin->prefixDos .  "Please enter all parameters for Infected.");
                $sender->sendMessage("INF - /manage <arena> INF <team> [spawn|pos1|pos2]");
                return false;
            }
            if($gm == "QUEUE" || $gm == "Q") {
                if(isset($args[2])) {
                    $username = strtolower($sender->getName());
                    $this->plugin->admin[$username]["queue"] = [$arena, strtolower($args[2])];
                    $sender->sendMessage($this->plugin->prefix .  "Break a block to set queue room point.");
                    return true;
                }
            }
            if($gm) {
                $sender->sendMessage($this->plugin->prefixDos . "Gamemode doesn't exist. CTF and INF is the only current game available.");
                return false;
            }
        }
        $sender->sendMessage("Please enter a gamemode. /manage <arena> <gamemode>");
        return false;
	}
}