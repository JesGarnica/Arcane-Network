<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\Main;

use pocketmine\Player;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};

class Repair extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct('repair', $plugin);
        $this->plugin = $plugin;
        $this->setPermission('arc.cmd.repair');
        $this->setDescription("Repair tools and armor");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player || count($args) > 1) {
        	$sender->sendMessage("/repair <hand|all>");
            return false;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("/repair <hand|all>");
            return false;
        }
        if(!(strtolower($args[0]) === "hand" || strtolower($args[0]) === "all")) {
            $sender->sendMessage("/repair <hand|all>");
            return false;
        }
        if(strtolower($args[0]) === "all"){
            foreach($sender->getInventory()->getContents() as $item) {
                if($this->plugin->isRepairable($item)) {
                    $item->setDamage(0);
                }
            }
            $m = $this->plugin->prefix . "All the tools in your inventory were repaired!";
            foreach($sender->getInventory()->getArmorContents() as $item) {
            	if($this->plugin->isRepairable($item)) {
            		$item->setDamage(0);
            	}
            }
            $m .= $this->plugin->prefix . " (Including the equipped Armor)";
        }else{
            if(!$this->plugin->isRepairable($sender->getInventory()->getItemInHand())){
                $sender->sendMessage($this->plugin->prefixDos . "This item can't be repaired!");
                return false;
            }
            $sender->getInventory()->getItemInHand()->setDamage(0);
            $m = $this->plugin->prefix . "Item successfully repaired!";
        }
        $sender->sendMessage($m);
        return true;
	}
}