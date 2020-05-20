<?php

namespace danixl\fac\listener;

use danixl\fac\Main;

use danixl\fac\task\LogTask;
use pocketmine\event\Listener;

use pocketmine\utils\TextFormat;

use pocketmine\Player;

use pocketmine\math\Vector3;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityShootBowEvent;

use pocketmine\event\block\SignChangeEvent;

use pocketmine\level\particle\DestroyBlockParticle;

use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

use pocketmine\tile\Sign;
use pocketmine\block\Block;
use pocketmine\item\Item;

class EventListener implements Listener {

	public $kills = [];

	public $pvpLocation, $pvpLocation2;

	private $plugin, $server;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->server = $plugin->getServer();
		$this->pvpLocation = $this->plugin->getWarp()->getWarpPosition("forest");
        $this->pvpLocation = $this->plugin->getWarp()->getWarpPosition("deadzone");
	}

	/*public function checkInside(Player $player) {
		$v = new Vector3($player->getLevel()->getSpawnLocation()->getX(), $player->getPosition()->getY(), $player->getLevel()->getSpawnLocation()->getZ());
		$radius = 100;
		$world = $this->server->getLevelByName($this->pvpWorldName);

		if($player->getLevel() == $world) {
			if($player->getPosition()->distance($v) <= $radius) {
				return true;
			}
			else {
				return false;
			}
		}
	}

	public function isBlockInside(Block $block) {
		$v = new Vector3($block->getLevel()->getSpawnLocation()->getX(), $block->getY(), $block->getLevel()->getSpawnLocation()->getZ());
		$radius = 100;
		$world = $this->server->getLevelByName($this->pvpWorldName);

		if($block->getLevel() == $world) {
			if($block->distance($v) <= $radius) {
				return true;
			} 
			else {
				return false;
			}
		}
	}*/

	public function onPreLogin(PlayerPreLoginEvent $event) {
		$player = $event->getPlayer();
		$this->plugin->createPlayer($player);
	}

	public function onJoin(PlayerJoinEvent $event) {
		$event->setJoinMessage(false);
		$player = $event->getPlayer();
		$username = $player->getName();
		$this->kills[strtolower($username)] = 0;
		if($player->hasPermission("arc.cmd.heal")) {
			$this->plugin->getPlayer($player)->setHealUsage(2);
		}
		$this->plugin->playerRegistered($username);
        $this->plugin->getFTManager()->spawnFloatingTextParticleTo($player);
        $player->addTitle("§l§bArcane §dFactions", "§lWhere Legends Battle!");
	}

	public function onQuit(PlayerQuitEvent $event) {
		$event->setQuitMessage(false);
		$player = $event->getPlayer();
		$username = strtolower($player->getName());
        if(isset($this->plugin->edit[$username])) unset($this->plugin->edit[$username]);
        if(isset($this->plugin->tpRequests[$username])) unset($this->plugin->tpRequests[$username]);
		if(isset($this->kills[$username])) unset($this->kills[$username]);
        if($this->plugin->getPlayer($player)->isLogged() && isset($this->plugin->logId[$username])) {
            $player->kill();
            $this->plugin->getPlayer($player)->delLog();
            $this->plugin->getScheduler()->cancelTask($this->plugin->logId[$username]);
            unset($this->plugin->logId[$username]);
            $this->server->broadcastMessage("§l§o§6» §7" . $player->getName() . " §r§o§7» §r§l§3Combat Logged!");
        }
        $this->plugin->getPlayer($player)->saveHealUsage();
		if($this->plugin->getPlayer($player)) {
			$this->plugin->destroyPlayer($player);
		}
		$this->plugin->getPerm()->delAttachment($player);
	}

	public function onRespawn(PlayerRespawnEvent $event) {
		$player = $event->getPlayer();
		$lobby = $this->server->getDefaultLevel()->getSpawnLocation();

		if($event->getRespawnPosition() !== $this->pvpLocation) {
		    if($this->plugin->getPlayer($player)->homeExists("default")) {
                $event->setRespawnPosition($this->plugin->getPlayer($player)->getHomePosition("default"));
            }
            else {
		        $event->setRespawnPosition($lobby);
            }
        }
        elseif($event->getRespawnPosition() !== $this->pvpLocation2) {
            if($this->plugin->getPlayer($player)->homeExists("default")) {
                $event->setRespawnPosition($this->plugin->getPlayer($player)->getHomePosition("default"));
            }
            else {
                $event->setRespawnPosition($lobby);
            }
        }
	}

	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$event->setFormat($this->plugin->getPlayer($player)->getChatFormat() . " " . $message);
	}

	public function onDeath(PlayerDeathEvent $event) {
		$entity = $event->getEntity();
		$cause = $entity->getLastDamageCause();
		$event->setDeathMessage(false);

		if($entity->hasPermission("arc.cmd.heal")) {
			$this->plugin->getPlayer($entity)->setHealUsage(2);
		}

		if($cause instanceof EntityDamageByEntityEvent) {
			if($cause->getDamager() instanceof Player) {
				$player = $cause->getDamager();
				$username = $player->getName();
				$low_username = strtolower($username);

				if($this->plugin->isPlayerStillOnTask("syncCoins", $username) || $this->plugin->isPlayerStillOnTask("syncKD", $username) || $this->plugin->isPlayerStillOnTask("syncRanks", $username)) {
					return true;
				}

				$this->plugin->getPlayer($player)->addKills(1);
				$this->kills[$low_username] = $this->kills[$low_username] + 1;
				$kills = $this->kills[$low_username];

				switch($kills) {

					case 2:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§1Double Kill§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 3:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§9Triple Kill§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 4:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§3Another Kill§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 5:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§bOver Kill§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 6:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§2Expert Assassin§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 7:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§aSavage§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 8:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§eRampage§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 9:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§6Super Saiyan§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 10:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§cDominating§7! §r§8(§7" . $kills . "K§8)");
						break;

					case 11:
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§4Unstoppable§7! §r§8(§7" . $kills . "K§8)");
						break;

					case ($kills >= 12):
						$this->server->broadcastMessage("§l§6» §7" . $username . " §r§o§7» §r§l§4Broke §athe §9Game§7! §r§8(§7" . $kills . "K§8)");
						break;
				}
			}
			if($entity instanceof Player) {
				$username = $entity->getName();
				$low_username = strtolower($username);
				if($this->plugin->isPlayerStillOnTask("syncKD", $username)) {
					return true;
				}

				$kills = $this->kills[$low_username];

				if($kills == 0) {
					$this->plugin->getPlayer($entity)->addDeaths(1);
					unset($this->kills[$low_username]);
					$this->kills[$low_username] = 0;
				}
				else {
					$this->plugin->getPlayer($entity)->addDeaths(1);
					unset($this->kills[$low_username]);
					$this->kills[$low_username] = 0;
					$entity->sendMessage($this->plugin->prefixTres . "Your kill streak has been reset.");
				}
			}
		}
		elseif($cause instanceof EntityDamageEvent) {
			$username = $entity->getName();
			$low_username = strtolower($username);


			if($this->plugin->isPlayerStillOnTask("syncKD", $username)) {
				return true;
			}
			$kills = $this->kills[$low_username];

			if($kills == 0) {
				$this->plugin->getPlayer($entity)->addDeaths(1);
				unset($this->kills[$low_username]);
				$this->kills[$low_username] = 0;
			}
			else {
				$this->plugin->getPlayer($entity)->addDeaths(1);
				unset($this->kills[$low_username]);
				$this->kills[$low_username] = 0;
				$entity->sendMessage($this->plugin->prefixTres . "Your kill streak has been reset.");
			}
		}
	}

    /**
     * @param $event
     * @priority NORMAL
     * @return bool
     */

	public function Damage(EntityDamageEvent $event) {
		$entity = $event->getEntity();

        if($event->getEntity() instanceof Player){
            $player = $event->getEntity();
            if(!$this->plugin->getArea()->canGetHurt($player)){
                $event->setCancelled();
                return false;
            }
        }

		if($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
    		if($entity->getLevel()->getName() == $this->server->getDefaultLevel()->getName()) $event->setCancelled();
  		}

		if($event instanceof EntityDamageByEntityEvent) {
			$attacker = $event->getDamager();
			if($attacker instanceof Player) {
				if($this->plugin->getPlayer($attacker)->isFlightOn()) {
					$event->setCancelled();
					return true;
				}
				$username = strtolower($attacker->getName());
                if(!$event->isCancelled()) {
                    if(!$this->plugin->getPlayer($attacker)->isLogged() && !isset($this->plugin->logId[$username])) {
                        $this->plugin->getPlayer($attacker)->log();
                        $this->plugin->logId[$username] = $this->plugin->getScheduler()
                            ->scheduleDelayedTask(new LogTask($this->plugin, $attacker), 300)->getTaskId();
                        $attacker->sendMessage($this->plugin->prefixDos . "You're now in combat. Wait 15 seconds...");
                        $attacker->sendMessage($this->plugin->prefixDos . "Escaping the match will result in death.");
                    }
                }
			}
			if($entity instanceof Player) {
				if(!$event->isCancelled()) {
					$entity->getLevel()->addParticle(new DestroyBlockParticle($entity, Block::get(Block::REDSTONE_BLOCK)));
                	$username = strtolower($entity->getName());
                	if(!$this->plugin->getPlayer($entity)->isLogged() && !isset($this->plugin->logId[$username])) {
                        $this->plugin->getPlayer($entity)->log();
                        $this->plugin->logId[$username] = $this->plugin->getScheduler()
                            ->scheduleDelayedTask(new LogTask($this->plugin, $entity), 300)->getTaskId();
                        $entity->sendMessage($this->plugin->prefixDos . "You're now in combat. Wait 15 seconds...");
                        $entity->sendMessage($this->plugin->prefixDos . "Espacing the match will result in 
                        		death.");
                    }
				}
            }
		}
	}

	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();

		if($event->getBlock()->getId() == Item::SIGN_POST || $event->getBlock()->getId() == Item::WALL_SIGN) {
			$sign = $player->getLevel()->getTile($event->getBlock());
			if($sign instanceof Sign) {
				$sign = $sign->getText();
				if($sign[0] == "[Kit]") {
					$kit = TextFormat::clean($sign[1]);
					if($this->plugin->getKit()->isKit($kit)) {
						if($player->hasPermission("arc.kit." . strtolower($kit))) {
							$p = $this->plugin->getPlayer($player);
							if($p->isKitUsed($kit)) {
								if($p->isKitCooling($kit)) {
									$seconds = $p->getKitUnit($kit);
									$player->sendMessage($this->plugin->prefixDos . "Please wait " . $seconds . " seconds until you can use this kit again.");
								}
								else {
									$player->sendMessage($this->plugin->prefixDos . "This kit is not available at the moment. Please wait until kit's cooldown is done.");
								}
							}
							else {
								$this->plugin->getKit()->giveKit($player, $kit);
							}
						}
						else {
							$player->sendMessage($this->plugin->prefixDos . "You don't have permission to use the " . $kit . " kit.");
						}
					}
					else {
						$player->sendMessage($this->plugin->prefixDos . "Kit doesn't exist.");
					}
				} 
			}
		}
	}

	public function onCreation(SignChangeEvent $event) {
		$player = $event->getPlayer();

		if($player->hasPermission("arc.kit-sign")) {
			$line = [strtolower($event->getLine(0)), strtolower($event->getLine(1))];
			if($line[0] == "kit") {
				if(empty($line[1])) {
					$player->sendMessage($this->plugin->prefixDos . "Please type in the correct format.");
					return false;
				}
				if($this->plugin->getKit()->isKit($line[1])) {
					$event->setLine(0, "[Kit]");
					$event->setLine(1, $line[1]);
					$player->sendMessage($this->plugin->prefix . "Kit sign was successfully created.");
				}
				else {
					$player->sendMessage($this->plugin->prefixDos . "Kit doesn't exist.");
				}
			}                    
		}
	}

	public function shootBow(EntityShootBowEvent $event) {
        $entity = $event->getEntity();
        
        if($entity instanceof Player) {
            if($this->plugin->getPlayer($entity)->isFlightOn() && $entity->getAllowFlight()) {
                $event->setCancelled();
                $entity->sendPopup($this->plugin->prefixDos . "You can't shoot projectiles while in flight mode");
            }
        }
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        $entity = $event->getEntity();

        if($entity instanceof Player) {
            $this->plugin->getFTManager()->updateFloatingTextByLevel($entity, $event->getTarget()->getName());
            if($event->getTarget()->getName() == $this->server->getDefaultLevel()->getName()) {
                if($this->plugin->getPlayer($entity)->isFlightOn()) {
                	$this->plugin->getPlayer($entity)->toggleFlight();
                }
            }
        }
    }

    public function onDataReceive(DataPacketReceiveEvent $event){
    	$pk = $event->getPacket();
    	if($pk::NETWORK_ID === ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET){
    		if(!$event->getPlayer()->isOp()) {
    			$event->setCancelled();
    			$event->getPlayer()->getLevel()->getTile(new Vector3($pk->x, $pk->y, $pk->z))->spawnTo($event->getPlayer());
    		}
    	}
    }
}