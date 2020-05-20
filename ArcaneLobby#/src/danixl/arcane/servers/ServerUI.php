<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 10/12/18
 * Time: 12:41 PM
 */

namespace danixl\arcane\servers;


use danixl\arcane\Main;

use danixl\arcane\utils\form\CustomForm;
use danixl\arcane\utils\form\ModalForm;
use danixl\arcane\utils\form\SimpleForm;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class ServerUI {

    private $plugin, $server;

    private $fullNames = ["CTF" => "Capture the Flag", "INF" => "Infected"];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
    }

    public function createMainUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectSubUI']);
        $form->setTitle("Server Selector");
        $form->setContent("Select a gamemode:");
        $cache = $this->plugin->getServerStats()->cacheServers;
        $categories = array_keys($cache);
        foreach($categories as $c) {
            if(isset($this->fullNames[$c])) {
                $name = $this->fullNames[$c];
            }
            else {
                $name = $c;
            }
            $form->addButton($name, -1, "", $c); // TODO: ADD ICONS FOR GAMES
        }
        $player->sendForm($form);
    }

    public function selectSubUI(Player $player, ?string $data): void {
        if($data == null) {
            return;
        }
        $category = $data;
        if(isset($this->plugin->getServerStats()->cacheServers[$category])) {
            $form = new SimpleForm([$this, 'transferPlayer']);
            if(isset($this->fullNames[$category])) {
                $name = $this->fullNames[$category];
            }
            else {
                $name = "Server Selector";
            }
            $form->setTitle($name);
            $form->setContent("Select a server:");
            $category = $this->plugin->getServerStats()->cacheServers[$category];
            foreach($category as $name => $data) {
                $address = $data[0] . "@" . $data[1];
                $status = $this->plugin->getServerStats()->getPlayerSlots($address);
                $name = "§l" . $name . " §r" . $status;
                $form->addButton($name, -1, "", $address);
            }
            $player->sendForm($form);
        }
        return;
    }

    public function transferPlayer(Player $player, ?string $data): void  {
        if($data == null) {
            return;
        }
        $fullAddress = $data;
        $address = explode("@", $fullAddress);
        $ip = $address[0];
        $port = $address[1];
        if(!$this->plugin->getServerStats()->getServerOnline()[$fullAddress]){
            return;
        }
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($this->plugin, $player, $ip, $port) extends Task {
                /** @var Player */
                private $player;
                /** @var string */
                private $ip;
                /** @var int */
                private $port;

                public function __construct(Main $plugin, Player $player, string $ip, int $port){
                    $this->player = $player;
                    $this->ip = $ip;
                    $this->port = $port;
                }

                public function onRun(int $currentTick){
                    $this->player->transfer($this->ip, $this->port, "Transfer/".implode("@", [$this->ip, $this->port]));
                }
            },
            1
        );
        return;
    }

    public function createServerUI(Player $player) {
        $form = new CustomForm([$this, 'ServerTransferCreation']);
        $form->setTitle('Server Transfer Creation');
        $form->addDropdown("Category", ["CTF", "INF"], 0, "cat");
        $form->addInput("Enter server name:", "unknown", "example-1");
        $form->addInput("Enter a valid IP:", "ip", "127.0.0");
        $form->addInput("Enter a valid port:", "port", "19132");
        $player->sendForm($form);
    }

    public function ServerTransferCreation(Player $player, ?array $data): void {
        if($data == null) {
            return;
        }
        $category = ($data["cat"] == 0 ? "CTF" : "INF"); // TODO: SWITCH CONDITION STATEMENT INSTEAD.
        $name = $data[1];
        if(strtolower($name) == "example-1" || empty($name)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Enter an actual name.");
            return;
        }
        $ip = $data[2];
        if($ip == "127.0.0" || empty($ip)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Server address cannot be localhost.");
            return;
        }
        $port = $data[3];
        if(!is_numeric($port) || empty($port)) {
            $player->sendMessage(TextFormat::GRAY . "[Arcane] Port must be a number.");
            return;
        }
        $this->plugin->getServerStats()->addServerToConfig($category, $name, $ip, $port);
        $this->plugin->getServerStats()->addServer($ip, $port);
        $player->sendMessage(TextFormat::GRAY . "[Arcane] Created server transfer on NPC.");
    }
}