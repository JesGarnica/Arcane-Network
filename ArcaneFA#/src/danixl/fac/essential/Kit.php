<?php

namespace danixl\fac\essential;

use danixl\fac\Main;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\Player;

use pocketmine\utils\Color;
use pocketmine\utils\Config;

use pocketmine\permission\Permission;

class Kit {

	public $kitTimer = [];

	private $m, $server;

	private $kit;

	public function __construct(Main $m) {
		$this->m = $m;
		$this->server = $m->getServer();
		$this->loadKits();
	}

	private function loadKits() {
        if(!file_exists($this->m->getDataFolder() . "kits.yml")) {
            $this->m->getLogger()->info("It seems like the kit file doesn't exist. Creating a new kit file...");
            $kit = new Config($this->m->getDataFolder() . "kits.yml", Config::YAML);
            $kitData = [
                "armor" => [310 => [0, 1]],
                "items" => [264 => [0, 1], 364 => [0, 32]],
                "secs" => 120
            ];
            $kit->set("scout", $kitData);
            $kit->save();
        }
        $kit = (new Config($this->m->getDataFolder() . "kits.yml", Config::YAML))->getAll();
        $this->kit = $kit;
        foreach($this->kit as $k => $kitData) {
            $kit = strtolower($k);
            $perm = new Permission("arc.kit.$kit", "Receive $k kit", "op");
            $this->server->getPluginManager()->addPermission($perm);
            $perm->addParent("arc.kit", true);
        }
    }

    public function isKit($kit) {
        if(isset($this->kit[$kit])) {
            return true;
        }
        return false;
    }
	
	public function getKitTime($kit) {
	   if($this->isKit($kit)) {
		   return $this->kit[$kit]["secs"];
	   }
	}
	 
	private function giveKitOld(Player $player, $kit) {
		if($this->kits->exists($kit)) {
			$k = $this->kits->get($kit)["items"];
			foreach($k as $id => $d) {
				if(!is_int($id)) {
					$player->sendMessage($this->emsg . "There appears to be an internal server error.");
					$this->getLogger()->warning("Kit item ids can only be integers, a string was found. Perhaps bad config indentation?");
					return false;
				}
				if(isset($d[2]) && isset($d[3])) {
					$enchantId = $d[2];
					$enchantLvl = $d[3];
					$enchantment = Enchantment::getEnchantment($enchantId);
					if($enchantment->getId() === Enchantment::TYPE_INVALID) {
						$player->sendMessage($this->emsg . "Invalid enchantment Id in kit.");
						$item = Item::get($id, $d[0], $d[1]);
					}
					else {
						$enchantment->setLevel($enchantLvl);
						$item = Item::get($id, $d[0], $d[1]);
						$item->addEnchantment($enchantment);
					}
				}
				else {
					$item = Item::get($id, $d[0], $d[1]);
				}
				$player->getInventory()->addItem($item);
			}
			$defaultSecs = $this->getKitTime($kit);
			$this->getPlayer($player)->useKit($kit, $defaultSecs);
			$player->sendMessage($this->msg . "You have recieved the " . $kit . " kit!");
			$player->sendMessage($this->msg . "Kit cool down for " . $kit . " of " . $defaultSecs . " second(s) has now started.");
		}
	}

    public function giveKit(Player $player, $kit) {
        if(isset($this->kit[$kit])) {
            $a = $this->kit[$kit]["armor"];
            $k = $this->kit[$kit]["items"];
            foreach($a as $ar => $d) {
                if(!is_int($d[0])) {
                    $player->sendMessage($this->m->prefix .  "There appears to be an internal server error.");
                    $this->m->getLogger()->warning("Kit armor ids can only be integers, a string was found. Perhaps bad config indentation?");
                    return false;
                }
                if(isset($d[1]) && isset($d[2])) {
                    if(is_int($d[1]) && is_int($d[2])) {
                        $enchantId = $d[1];
                        $enchantLvl = $d[2];
                        $enchantment = Enchantment::getEnchantment($enchantId);
                        $enchantment = new EnchantmentInstance($enchantment);
                        $armor = Armor::get($d[0], 0, 1);
                        if(!$enchantment) {
                            $player->sendMessage($this->m->prefix .  "Invalid enchantment ID in kit.");
                        }
                        else {
                            $enchantment->setLevel($enchantLvl);
                            $armor->addEnchantment($enchantment);

                        }
                    }
                }
                else {
                    $armor = Armor::get($d[0], 0, 1);
                }
                if(isset($d[3])) {
                    if($armor->getId() == 298 || $armor->getId() == 299 || $armor->getId() == 300 || $armor->getId() == 301) {
                        $split = explode(",", $d[3]);
                        $r = (int)$split[0];
                        $g = (int)$split[1];
                        $b = (int)$split[2];
                        $color = new Color($r, $g, $b);
                        $armor->setCustomColor($color);
                    }
                }
                switch($ar) {
                    case "helmet":
                        $player->getArmorInventory()->setHelmet($armor);
                        break;

                    case "chest":
                        $player->getArmorInventory()->setChestplate($armor);
                        break;

                    case "legg":
                        $player->getArmorInventory()->setLeggings($armor);
                        break;

                    case "boots":
                        $player->getArmorInventory()->setBoots($armor);
                        break;

                    default:
                        $player->sendMessage($this->m->prefix .  "Invalid armor. Contact staff for help.");
                }
            }
            $player->getArmorInventory()->sendContents($player);

            foreach($k as $id => $d) {
                if(!is_int($id)) {
                    $player->sendMessage($this->m->prefix .  "There appears to be an internal server error.");
                    $this->m->getLogger()->warning("Kit item ids can only be integers, a string was found. Perhaps bad config indentation?");
                    return false;
                }
                if(isset($d[2]) && isset($d[3])) {
                    if(is_int($d[2]) && is_int($d[3])) {
                        $enchantId = $d[2];
                        $enchantLvl = $d[3];
                        $enchantment = Enchantment::getEnchantment($enchantId);
                        $enchantment = new EnchantmentInstance($enchantment);
                        $enchantment->setLevel($enchantLvl);
                        if(!$enchantment) {
                            $player->sendMessage($this->m->prefix .  "Invalid enchantment ID in kit.");
                            $item = Item::get($id, $d[0], $d[1]);
                        }
                        else {
                            $item = Item::get($id, $d[0], $d[1]);
                            $item->addEnchantment($enchantment);
                        }
                    }
                }
                else {
                    $item = Item::get($id, $d[0], $d[1]);
                }
                if(isset($d[4])) {
                    if(is_string($d[4])) {
                        $item->setCustomName($d[4]);
                    }
                }
                $player->getInventory()->addItem($item);
            }
            $defaultSecs = $this->getKitTime($kit);
            $this->m->getPlayer($player)->useKit($kit, $defaultSecs);
            $player->sendMessage($this->m->prefix . "You have received the " . $kit . " kit!");
            $player->sendMessage($this->m->prefix . "Kit cool down for " . $kit . " of " . $defaultSecs . " second(s) has now started.");
        }
    }
}