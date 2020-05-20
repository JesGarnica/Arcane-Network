<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class template extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('temp', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.temp');
        $this->setDescription("Template");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
		}
	}
}