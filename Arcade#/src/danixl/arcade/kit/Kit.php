<?php

declare(strict_types=1);

namespace danixl\arcade\kit;

use danixl\arcade\Arcade;

use danixl\arcade\utils\form\CustomForm;
use danixl\arcade\utils\form\SimpleForm;

use pocketmine\item\Armor;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

use pocketmine\utils\Color;
use pocketmine\utils\Config;

use pocketmine\item\Item;

use pocketmine\permission\Permission;

use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Kit {

     private $kit, $kitUsed = [];

     private $spamTimer;
     
     public function __construct() {
         $this->plugin = Arcade::getAPI();
         $this->server = $this->plugin->getServer();
         if(!file_exists($this->plugin->getDataFolder() . "kits.yml")) {
             $this->plugin->getLogger()->info("It seems like the kit file doesn't exist. Creating a new kit file...");
             $kit = new Config($this->plugin->getDataFolder() . "kits.yml", Config::YAML);
             $kitData = [
                 "armor" => [310 => [0, 1]],
                 "items" => [264 => [0, 1], 364 => [0, 32]]
             ];
             $kit->set("scout", $kitData);
             $kit->save();
         }
         $kit = (new Config($this->plugin->getDataFolder() . "kits.yml", Config::YAML))->getAll();
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

    public function giveKit(Player $player, $kit) {
        if(isset($this->kit[$kit])) {
            $a = $this->kit[$kit]["armor"];
            $k = $this->kit[$kit]["items"];
            foreach($a as $ar => $d) {
                if(!is_int($d[0])) {
                    $player->sendMessage($this->plugin->prefix .  "There appears to be an internal server error.");
                    $this->plugin->getLogger()->warning("Kit armor ids can only be integers, a string was found. Perhaps bad config indentation?");
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
                            $player->sendMessage($this->plugin->prefix .  "Invalid enchantment ID in kit.");
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
                        $player->sendMessage($this->plugin->prefix .  "Invalid armor. Contact staff for help.");
                }
            }
            $player->getArmorInventory()->sendContents($player);

            foreach($k as $id => $d) {
                if(!is_int($id)) {
                    $player->sendMessage($this->plugin->prefix .  "There appears to be an internal server error.");
                    $this->plugin->getLogger()->warning("Kit item ids can only be integers, a string was found. Perhaps bad config indentation?");
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
                            $player->sendMessage($this->plugin->prefix .  "Invalid enchantment ID in kit.");
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
        }
    }
     
     public function kitUsed(Player $player): bool {
         $username = strtolower($player->getName());
         
         if(isset($this->kitUsed[$username])) {
             return true;
         }
         return false;
     }
     
     public function kitIsUsed(Player $player, $kit) {
         $username = strtolower($player->getName());
         
         if(!isset($this->kitUsed[$username])) {
             $this->kitUsed[$username] = $kit;
         }
     }
     
     public function getKitUsed(Player $player) {
         $username = strtolower($player->getName());
         
         if(isset($this->kitUsed[$username])) {
             return $this->kitUsed[$username];
         }
     }
     
     public function giveKitUsed(Player $player) {
         $username = strtolower($player->getName());
         
         if(isset($this->kitUsed[$username])) {
             $this->giveKit($player, $this->kitUsed[$username]);
         }
     }
     
     public function rmKitUsed(Player $player) {
         $username = strtolower($player->getName());
         
         if(isset($this->kitUsed[$username])) {
             unset($this->kitUsed[$username]);
         }
     }

     public function createKitUI(Player $player) {
         $form = new CustomForm([$this, 'kitFormSelection']);
         $form->setTitle('Class Selector');
         $form->addLabel("Select a class to use different kits and abilities.");
         $available = [];
         foreach($this->kit as $kit => $kitData) {
             if($player->hasPermission("arc.kit." . $kit)) {
                 $name = ucfirst($kit);
                 array_push($available, $name);
             }
             else {
                 $name = TextFormat::RED . ucfirst($kit);
                 array_push($available, $name);
             }
         }
         $form->addDropdown("Select Class", $available, 0, "class");
         $player->sendForm($form);
     }

    public function kitFormSelection(Player $player, ?array $data): void {
        if($data == null) {
            return;
        }
        $i = $data["class"];
        $allKeys = array_keys($this->kit);
        $kit = $allKeys[$i];
        if($this->plugin->getKit()->isKit($kit)) {
            if($player->hasPermission("arc.kit." . $kit)) {
                if($this->plugin->getKit()->kitUsed($player)) {
                    if($this->plugin->getKit()->getKitUsed($player) == $kit) {
                        $player->sendMessage($this->plugin->prefixDos .  "You already have selected this class.");
                    }
                    else {
                        $this->plugin->getKit()->rmKitUsed($player);
                        $this->plugin->getKit()->kitIsUsed($player, $kit);
                        $player->sendMessage($this->plugin->prefix .  "You have changed to " . $kit . " class.\nWill be applied on respawn.");
                    }
                }
                else {
                    $this->plugin->getKit()->kitIsUsed($player, $kit);
                    $player->sendMessage($this->plugin->prefix .  "You have selected the " . $kit . " class!");
                }
            }
            else {
                $player->sendMessage($this->plugin->prefixTres .  "Upgrade your rank on http://arcn.us to access the " . $kit . " class.");
            }
        }
        else {
            $player->sendMessage($this->plugin->prefixDos .  "Class doesn't exist.");
        }
    }
}