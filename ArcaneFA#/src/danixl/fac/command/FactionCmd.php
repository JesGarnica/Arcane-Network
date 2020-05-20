<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class FactionCmd extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('f', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.fac');
        $this->setDescription("Create and Manage Factions");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
		}
        if(!isset($args[0])) {
            $this->plugin->getFac()->createMainUI($sender);
            return true;
        }
        $command = strtolower($args[0]);
        switch($command){

            case "accept":
                $this->plugin->getFac()->processInviteResponse($sender, "accept");
                break;

            case "deny":
            case "decline":
                $this->plugin->getFac()->processInviteResponse($sender, "deny");
                break;

            default:
                $this->plugin->getFac()->createMainUI($sender);
        }
        return false;
	}
}