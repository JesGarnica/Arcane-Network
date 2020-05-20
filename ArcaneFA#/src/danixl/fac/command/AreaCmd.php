<?php
declare(strict_types=1);

namespace danixl\fac\command;

use danixl\fac\area\Area;

use danixl\fac\Main;

use pocketmine\command\{
    CommandSender,
    PluginCommand
};
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AreaCmd extends PluginCommand {

    private $plugin, $server;

    public function __construct(Main $plugin) {
        parent::__construct('area', $plugin);
        $this->plugin = $plugin;
        $this->server = $plugin->getServer();
        $this->setPermission('arc.cmd.area');
        $this->setDescription("Manage/edit arena");

    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return true;
		}
        if(!($sender instanceof Player)){
            $sender->sendMessage("Please run this command in-game.");
            return false;
        }
        if(!isset($args[0])){
            $sender->sendMessage("Area Commands");
            $sender->sendMessage("/area pos1 or pos2");
            $sender->sendMessage("/area create <name>");
            $sender->sendMessage("/area delete <name>");
            $sender->sendMessage("/area list");
            $sender->sendMessage("/area here");
            $sender->sendMessage("/area tp");
            $sender->sendMessage("/area flag <name> <flag> <on|off>");
            $sender->sendMessage("/area whitelist add|delete|list");
            return false;
        }
        $playerName = strtolower($sender->getName());
        $action = strtolower($args[0]);
        $o = "";

        switch($action){
            case "pos1":
                if(isset($this->plugin->getArea()->selectingFirst[$playerName]) || isset($this->plugin->getArea()->selectingSecond[$playerName])){
                    $o = $this->plugin->prefixDos . "You're already selecting a position!";
                }
                else {
                    $this->plugin->getArea()->selectingFirst[$playerName] = true;
                    $o = $this->plugin->prefix . "Please place or break the first position.";
                }    
                break;
            case "pos2":
                if(isset($this->selectingFirst[$playerName]) || isset($this->plugin->getArea()->selectingSecond[$playerName])){
                    $o = $this->plugin->prefixDos . "You're already selecting a position!";
                }
                else{
                    $this->plugin->getArea()->selectingSecond[$playerName] = true;
                    $o = $this->plugin->prefix . "Please place or break the second position.";
                }    
                break;
            case "create":
                if(isset($args[1])){
                    if(isset($this->plugin->getArea()->firstPosition[$playerName], $this->plugin->getArea()->secondPosition[$playerName])){
                        if(!isset($this->plugin->getArea()->areas[strtolower($args[1])])){
                            new Area(strtolower($args[1]), ["edit" => false, "damage" => false, "touch" => false], $this->plugin->getArea()->firstPosition[$playerName], $this->plugin->getArea()->secondPosition[$playerName], $sender->getLevel()->getName(), [$playerName], $this->plugin->getArea());
                            $this->plugin->getArea()->saveAreas();
                            unset($this->plugin->getArea()->firstPosition[$playerName], $this->plugin->getArea()->secondPosition[$playerName]);
                            $o = $this->plugin->prefixTres . "Area created!";
                        }
                        else{
                            $o = $this->plugin->prefixDos . "An area with that name already exists.";
                        }
                    }
                    else{
                        $o = $this->plugin->prefixDos . "Please select both positions first.";
                    }
                }
                else{
                    $o = $this->plugin->prefixDos . "Please specify a name for this area.";
                }    
                break;
            case "list":
                $o = $this->plugin->prefixTres . "Areas: " . TextFormat::RESET;
                $i = 0;
                foreach($this->plugin->getArea()->areas as $area){
                    if($area->isWhitelisted($playerName)){
                        $o .= $area->getName() . " (" . implode(", ", $area->getWhitelist()) . "), ";
                        $i++;
                    }
                }
                if($i === 0){
                    $o = "There are no areas that you can edit";
                }    
                break;
            case "here":
                $o = "";
                foreach($this->plugin->getArea()->areas as $area){
                    if($area->contains($sender->getPosition(), $sender->getLevel()->getName()) && $area->getWhitelist() !== null){
                        $o .= $this->plugin->prefixTres . "Area " . $area->getName() . " can be edited by " . implode(", ", $area->getWhitelist());
                        break;
                    }
                }
                if($o === "") {
                    $o = $this->plugin->prefixDos . "You are in an unknown area";
                }    
                break;
            case "tp":
                if (!isset($args[1])){
                    $o = $this->plugin->prefixDos . "You must specify an existing area name";
                    break;
                }
                $area = $this->plugin->getArea()->areas[strtolower($args[1])];
                if($area !== null && $area->isWhitelisted($playerName)){
                    $levelName = $area->getLevelName();
                    if(isset($levelName) && $this->server->loadLevel($levelName) != false){
                        $o = $this->plugin->prefix . "You are teleporting to area " . $args[1];
                        $sender->teleport(new Position($area->getFirstPosition()->getX(), $area->getFirstPosition()->getY() + 0.5, $area->getFirstPosition()->getZ(), $area->getLevel()));
                    }else{
                        $o = $this->plugin->prefixDos . "The level " . $levelName . " for area ". $args[1] ." cannot be found";
                    }
                }else{
                    $o = $this->plugin->prefixDos . "The area " . $args[1] . " could not be found ";
                }
                break;
            case "flag":
                if(isset($args[1])){
                    if(isset($this->plugin->getArea()->areas[strtolower($args[1])])){
                        $area = $this->plugin->getArea()->areas[strtolower($args[1])];
                        if(isset($args[2])){
                            if(isset($area->flags[strtolower($args[2])])){
                                $flag = strtolower($args[2]);
                                if(isset($args[3])){
                                    $mode = strtolower($args[3]);
                                    if($mode === "true" || $mode === "on"){
                                        $mode = true;
                                    }
                                    else{
                                        $mode = false;
                                    }
                                    $area->setFlag($flag, $mode);
                                }
                                else{
                                    $area->toggleFlag($flag);
                                }
                                if($area->getFlag($flag)){
                                    $status = "on";
                                }
                                else{
                                    $status = "off";
                                }
                                $o = $this->plugin->prefix . "Flag " . $flag . " set to " . $status . " for area " . $area->getName() . "!";
                            }
                            else{
                                $o = $this->plugin->prefixDos . "Flag not found. (Flags: edit, damage, touch)";
                            }
                        }
                        else{
                            $o = $this->plugin->prefixDos . "Please specify a flag. (Flags: edit, damage, touch)";
                        }
                    }
                    else{
                        $o = $this->plugin->prefixDos . "Area doesn't exist.";
                    }
                }
                else{
                    $o = $this->plugin->prefixDos . "Please specify the area you would like to flag.";
                }    
                break;
            case "delete":
                if(isset($args[1])){
                    if(isset($this->plugin->getArea()->areas[strtolower($args[1])])){
                        $area = $this->plugin->getArea()->areas[strtolower($args[1])];
                        $area->delete();
                        $o = $this->plugin->prefix . "Area deleted!";
                    }
                    else{
                        $o = $this->plugin->prefixDos . "Area does not exist.";
                    }
                }
                else{
                    $o = $this->plugin->prefixDos . "Please specify an area to delete.";
                }    
                break;
            case "whitelist":
                if(isset($args[1], $this->plugin->getArea()->areas[strtolower($args[1])])){
                    $area = $this->plugin->getArea()->areas[strtolower($args[1])];
                    if(isset($args[2])){
                        $action = strtolower($args[2]);
                        switch($action){
                            case "add":
                                $w = ($this->server->getPlayer($args[3]) instanceof Player ? strtolower($this->server->getPlayer($args[3])->getName()) : strtolower($args[3]));
                                if(!$area->isWhitelisted($w)){
                                    $area->setWhitelisted($w);
                                    $o = $this->plugin->prefix . "Player $w has been whitelisted in area " . $area->getName() . ".";
                                }else{
                                    $o = $this->plugin->prefixDos . "Player $w is already whitelisted in area " . $area->getName() . ".";
                                }
                                break;
                            case "list":
                                $o = $this->plugin->prefixTres . "Area " . $area->getName() . "'s whitelist:" . TextFormat::RESET;
                                foreach($area->getWhitelist() as $w){
                                    $o .= " $w;";
                                }
                                break;
                            case "delete":
                            case "remove":
                                $w = ($this->server->getPlayer($args[3]) instanceof Player ? strtolower($this->server->getPlayer($args[3])->getName()) : strtolower($args[3]));
                                if($area->isWhitelisted($w)){
                                    $area->setWhitelisted($w, false);
                                    $o = $this->plugin->prefix . "Player $w has been unwhitelisted in area " . $area->getName() . ".";
                                }else{
                                    $o = $this->plugin->prefixDos . "Player $w is already unwhitelisted in area " . $area->getName() . ".";
                                }
                                break;
                            default:
                                $o = $this->plugin->prefixDos . "Please specify a valid action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
                                break;
                        }
                    }else{
                        $o = $this->plugin->prefixDos . "Please specify an action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
                    }
                }else{
                    $o = $this->plugin->prefixDos . "Area doesn't exist. Usage: /area whitelist <area> <add/list/remove> [player]";
                }    
                break;
            default:
                return false;
        }
        $sender->sendMessage($o);
        return true;
	}
}