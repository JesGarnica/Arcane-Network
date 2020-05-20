<?php


declare(strict_types=1);

namespace danixl\arcade\command;

use pocketmine\Player;

use pocketmine\level\particle\HappyVillagerParticle;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

use danixl\arcade\Arcade;

class Feed extends PluginCommand {

    private $plugin;

    public function __construct(Arcade $plugin) {
        parent::__construct('feed', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.feed');
        $this->setDescription("Feed yourself");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if($sender instanceof Player) {
            $sender->setFood(20);
            $sender->getLevel()->addParticle(new HappyVillagerParticle($sender->getPosition()));
            $sender->sendMessage($this->plugin->prefix .  "You have fed yourself!");
            return true;
        }
        $sender->sendMessage("Please run this command in-game.");
        return false;
	}
}