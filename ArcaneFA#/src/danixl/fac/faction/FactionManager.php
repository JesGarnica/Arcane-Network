<?php

namespace danixl\fac\faction;

use danixl\fac\Main;

use danixl\fac\utils\form\CustomForm;
use danixl\fac\utils\form\SimpleForm;

use pocketmine\Player;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class FactionManager {

    private $plugin, $server;

    private $faction = [];

    private $rank = [];

    public $invite = [];

    public $allyInvite = [];

    // TODO: Create Faction Chat and Notification

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->server = $this->plugin->getServer();
        $this->rank = ['member' => TextFormat::YELLOW, 'officer' => TextFormat::GREEN, 'commander' => TextFormat::AQUA, 'leader' => TextFormat::RED];
        $this->loadFactions();
    }

    public function loadFactions() {
        $this->queryTask("syncFactions", null);
    }

    public function createNewFaction($factionName, $leader) {
        $this->queryTask("createFaction", $leader, $factionName);
    }

    public function getFaction(String $faction): Faction {
        $faction = strtolower($faction);

        if(isset($this->faction[$faction])) {
            return $this->faction[$faction];
        }
    }

    public function createFaction(String $faction, Array $data) {
        $lowcasedFactionName = strtolower($faction);

        if(!isset($this->faction[$lowcasedFactionName])) {
            $this->faction[$lowcasedFactionName] = new Faction($this, $faction, $data);
            return true;
        }
        return false;
    }

    public function factionExists(?String $faction) {
        if($faction) {
            $faction = strtolower($faction);

            if(isset($this->faction[$faction])) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function destroyFaction(String $faction): void {
        $faction = strtolower($faction);

        if(isset($this->faction[$faction])) {
            unset($this->faction[$faction]);
        }
    }

    public function getFactions() {
        return $this->faction;
    }

    public function isRank($rank) {
        if($this->rank[$rank]) {
            return true;
        }
        return false;
    }

    public function getRankColor($rank) {
        if($this->rank[$rank]) {
            return $this->rank[$rank];
        }
        return TextFormat::DARK_GRAY;
    }

    public function isNoble($rank) { // Must be officer, commander or leader to return true
        if($rank !== 'member') {
            if($this->isRank($rank)) {
                return true;
            }
        }
        return false;
    }

    public function isElite($rank) { // Must be commander or leader to return true
        if($rank == 'commander' || $rank == 'leader') {
            return true;
        }
        return false;
    }

    public function queryTask($queryType, $data, $faction = null){
        //$this->addPlayerOnTask($faction, $queryType);
        $this->server->getAsyncPool()->submitTask(new FactionQueryTask($queryType, $data, $faction));
    }

    public function queryTaskDone($queryType, $faction, $data, $data1 = null) {
        switch ($queryType) {

            case "syncFactions":
                $factions = unserialize($data);
                if($factions) {
                    foreach($factions as $fac) {
                        $this->queryTask("syncData", null, $fac);
                    }
                    $count = count($factions);
                    $this->plugin->getLogger()->info(TextFormat::GREEN . "Loaded " . TextFormat::AQUA . $count . TextFormat::GREEN . " faction(s)...");
                }
                else {
                    $this->plugin->getLogger()->info("No factions loaded. None found.");
                }
                break;

            case "syncData":
                // Creates a faction instance that is used on the server instance
                if($data) {
                    $data = json_decode($data, true);
                    if(isset($data['name'])) {
                        $this->createFaction($data['name'], $data);
                    }
                }
                break;

            case "createFaction":
                if($data) {
                    $defaultFactionData = [
                        'tier' => 1, // Tier
                        'leader' => $data, // Leader
                        'allies' => NULL, // Allies
                        'enemies' => NULL, // Enemies
                        'motd' => "§bA New Faction Arises", // Message of the Day
                        'coins' => 0, // Coins
                        'power' => 0, // Power
                        'homes' => NULL, // Homes
                        'members' => [strtolower($data) => 'leader'] // Members
                    ];
                    $this->createFaction($faction, $defaultFactionData);
                    $leader = $this->server->getPlayerExact($data);
                    $player = $this->plugin->getPlayer($leader);
                    $player->setFaction($faction);
                    $player->setFactionLeader(true);
                    $player->setFactionRank('leader');
                    $player->setFactionChatFormat();
                    $leader->sendMessage($this->plugin->prefixTres . "Faction" . TextFormat::GRAY .  ": " . TextFormat::RED .
                        $faction . TextFormat::LIGHT_PURPLE . " successfully created!");                }
                break;
        }
    }

    // BASIC FACTION METHODS

    public function sendFactionInfoForm(Player $player, $factionName) {
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $name = TextFormat::GREEN . $faction->getName();
            $motd = $faction->getMOTD();
            $leader = TextFormat::RED . $faction->getLeader();
            $memberCount = TextFormat::RED . count($faction->getMembers());
            $coinVault = TextFormat::GOLD . $faction->getCoins();
            $power = TextFormat::YELLOW . $faction->getPower();
            $tier = $faction->getTier();
            switch($tier) {
                case 1:
                    $tier = " (Tier I)";
                    break;
                case 2:
                    $tier = " (Tier II)";
                    break;

                case 3:
                    $tier = " (Tier III)";
                    break;
            }
            $form = new CustomForm(null);
            $form->setTitle("Faction - " . $name);
            $form->addLabel("Name: " . $name . TextFormat::LIGHT_PURPLE . $tier);
            $form->addLabel("MOTD: " . $motd);
            $form->addLabel("Leader: " . $leader);
            $form->addLabel("Members: " . $memberCount);
            $form->addLabel("Wealth: " . $coinVault . TextFormat::RED . " coins");
            $form->addLabel("Power: " . $power);
            $player->sendForm($form);
        }
    }

    public function sendFactionMembersForm(Player $player, $factionName){
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $members = $faction->getMembers();
            $form = new CustomForm(null);
            $form->setTitle("Faction Members Info:" . $factionName);
            $form->addLabel("All Members:");
            foreach($members as $u => $r) {
                $form->addLabel(TextFormat::LIGHT_PURPLE . strtoupper($u) . TextFormat::RESET . " - " . $this->getRankColor($r) . ucfirst($r));
            }
            $player->sendForm($form);
        }
    }

    public function previousForm(Player $player, ?string $data) {
        if($data == null) {
            return;
        }
        $data = explode(":" , $data);
        switch($data) {
            case "general":
                $this->createGeneralUI($player);
                break;

            case "main":
                $this->createMainUI($player);
                break;

            case "leader":
                $this->createLeaderUI($player);
                break;

            case "create":
                $this->createNewFactionUI($player);
                break;
        }
    }

    // INVITE SYSTEM

    public function getInvite($username) {
        $username = strtolower($username);

        if(isset($this->invite[$username])) {
            return $this->invite[$username];
        }
        return false;
    }

    public function isInvited($username) {
        $username = strtolower($username);

        if(isset($this->invite[$username])) {
            return true;
        }
        return false;
    }

    public function addInvite($senderUsername, $username, $factionName) {
        $username = strtolower($username);
        $senderUsername = strtolower($senderUsername);

        if(!isset($this->invite[$username])) {
            $data = [$factionName, $senderUsername];
            $this->invite[$username] = $data;
        }
    }

    public function rmInvite($username) {
        $username = strtolower($username);

        if(isset($this->invite[$username])) {
            unset($this->invite[$username]);
        }
    }

    public function processPlayerInvite(Player $player, ?array $data) {
        if($data == null) {
            return;
        }
        $targetUser = $data['username'];
        if(strtolower($targetUser) == 'steve' || empty($targetUser)) {
            $player->sendMessage($this->plugin->prefixDos . "Enter a valid online player.");
            return;
        }
        if(strtolower($player->getName()) == strtolower($targetUser)) {
            $player->sendMessage($this->plugin->prefixDos . "You cannot invite yourself.");
            return;
        }
        $target = $this->server->getPlayerExact($targetUser);
        if($target instanceof Player) {
            $tp = $this->plugin->getPlayer($target);
            $faction = $this->plugin->getPlayer($player)->getFaction();
            if($tp->hasFaction()) {
                $targetFac = $tp->getFaction();
                if($targetFac == $faction) {
                    $player->sendMessage($this->plugin->prefixDos . $targetUser . " already belongs to your faction");
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . $targetUser . " already belongs to a faction.");
                }
                return;
            }
            if($this->isInvited($targetUser)) {
               $player->sendMessage($this->plugin->prefixDos . "You have already invited this player.");
            }
            else {
                $this->addInvite($player->getName(), $targetUser, $faction);
                $this->sendInvite($player, $target, $faction);
                $player->sendMessage($this->plugin->prefixTres . "Sending invite...");
            }
        }
        else {
            $player->sendMessage($this->plugin->prefixDos . $targetUser . " doesn't exist.");
        }
    }

    public function sendInvite(Player $sender, Player $invitee, $factionName){
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $form = new SimpleForm([$this, 'processInviteResponse']);
            $form->setTitle("Faction Invite");
            $form->setContent($sender->getName() . " is inviting you to " . $faction->getName());
            $form->setContent("Click 'Accept Invite' or 'Deny Invite' to respond.");
            $form->addButton(TextFormat::GREEN . "Accept Invite", -1, "", "accept");
            $form->addButton(TextFormat::RED . "Deny Invite", -1, "", "deny");
            $form->addButton("View Faction Info", -1, "", "info");
            $form->setContent("/f accept and /f deny are also available.");
            $invitee->sendForm($form);
        }
    }

    public function processInviteResponse(Player $player, ?string $response) {
        if($response == null) {
            return;
        }
        if(!$this->isInvited($player->getName())) {
            $player->sendMessage($this->plugin->prefixDos . "You currently don't have a faction invite.");
            return;
        }
        if($response == "accept") {
            $factionName = $this->getInvite($player->getName())[0];
            $senderUsername = $this->getInvite($player->getName())[1];
            $sender = $this->server->getPlayerExact($senderUsername);
            $this->invitePlayer($sender, $player, $factionName);
            return;
        }
        if($response == "deny") {
            $senderUsername = $this->getInvite($player->getName())[1];
            $sender = $this->server->getPlayerExact($senderUsername);
            if($sender instanceof Player) {
                $sender->sendMessage($this->plugin->prefixDos . $player->getName() . " has denied your invite.");
            }
            $this->rmInvite($player->getName());
            $player->sendMessage($this->plugin->prefixTres . "You have denied " . $senderUsername . "'s invite.");
            return;
        }
        if($response == "info") {
            $factionName = $this->getInvite($player->getName())[0];
            $this->sendFactionInfoForm($player, $factionName);
        }
    }

    public function invitePlayer(?Player $sender, Player $invitee, $factionName) {
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $faction->setMember($invitee->getName(), 'member');
            $p = $this->plugin->getPlayer($invitee);
            $p->setFaction($factionName);
            $p->setFactionRank("member");
            $p->setFactionChatFormat();
            $this->rmInvite($invitee->getName());
            $invitee->sendMessage($this->plugin->prefix . "You have been successfully invited to " . TextFormat::RED . $faction->getName() . "!");
            if($sender instanceof Player) $sender->sendMessage($this->plugin->prefix . $invitee->getName() . " has been invited to " . TextFormat::RED . $faction->getName() . "!");
        }
    }

    // KICK SYSTEM

    public function processPlayerKick(Player $player, ?string $targetUser){
        if($targetUser == null) {
            return;
        }
        $factionName = $this->plugin->getPlayer($player)->getFaction();
        if(strtolower($player->getName()) == strtolower($targetUser)) {
            $player->sendMessage($this->plugin->prefixDos . "You cannot kick yourself.");
            return;
        }
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $faction->removeMember($targetUser);
            $target = $this->server->getPlayerExact($targetUser);
            if($target instanceof Player) {
                $tp = $this->plugin->getPlayer($target);
                $tp->setFaction(null);
                $tp->setFactionChatFormat();
                $target->sendMessage($this->plugin->prefix . "You have been kicked from your current faction.");
                $player->sendMessage($this->plugin->prefix . "You have successfully kicked " . TextFormat::LIGHT_PURPLE .  $target->getName() . TextFormat::GREEN . " from the faction.");
            }
            else {
                $this->plugin->queryTask("deletePlayerFac", [$targetUser], $player->getName());
                $player->sendMessage($this->plugin->prefix . "Kicking player from faction...");
            }
        }
    }

    // MOTD SYSTEM

    public function processMOTDChange(Player $player, ?array $motd) {
        $newMOTD = $motd[0];
        if($newMOTD == null) {
            $player->sendMessage($this->plugin->prefixDos . "MOTD cannot be empty.");
            return;
        }
        $factionName = $this->plugin->getPlayer($player)->getFaction();
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $currentMOTD = $faction->getMOTD();
            if($newMOTD == $currentMOTD) {
                $player->sendMessage($this->plugin->prefixDos . "You didn't change anything.");
                return;
            }
            if(strlen($newMOTD) < 3) {
                $player->sendMessage($this->plugin->prefixDos . "MOTD must at least contain 3 characters.");
                return;
            }
            if(strlen($newMOTD) > 25) {
                $player->sendMessage($this->plugin->prefixDos . "MOTD cannot contain more than 25 characters.");
                return;
            }
            if(!$this->alphanumMOTD($newMOTD)) {
                $player->sendMessage($this->plugin->prefixDos . "MOTD cannot contain any special characters.");
                return;
            }
            $faction->setMOTD($newMOTD);
            $player->sendMessage($this->plugin->prefix . "Successfully set a new Message of the Day!");
        }
    }

    public function createMainUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectSubUI']);
        $form->setTitle("Arcane Faction Manager");
        $p = $this->plugin->getPlayer($player);
        if($p->hasFaction()) {
            if($p->isFactionLeader()) {
                $form->addButton("Leader Control Board", -1, "", "leader");
            }
            if($this->isElite($p->getFactionRank())) {
                $form->addButton("Member Manager", -1, "", 'member');
            }
        }
        else {
            $form->addButton("Create Faction", -1, "", "create");
        }
        $form->addButton("General", -1, "", "general");
        $player->sendForm($form);
    }

    public function selectSubUI(Player $player, ?string $selection): void {
        if($selection == null) {
            return;
        }
        switch($selection) {
            case "general":
                $this->createGeneralUI($player);
                break;

            case "member":
                $this->createMemberManagerUI($player);
                break;

            case "leader":
                $this->createLeaderUI($player);
                break;

            case "create":
                $this->createNewFactionUI($player);
                break;
        }
    }

    // General Commands UI's

    public function createGeneralUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectGeneralCommand']);
        $form->setTitle("Arcane Faction Manager");
        $p = $this->plugin->getPlayer($player);
        $form->addButton("Top Factions", -1, "", 'top');
        if($p->hasFaction()) {
            $form->addButton("Faction Info", -1, "", 'info');
            $form->addButton("View Members", -1, "", 'members');
            $form->addButton("Teleport Home", -1, "", 'home');
            if($this->isElite($p->getFactionRank())) {
                $form->addButton("Set MOTD", -1, "", 'setmotd');
                $form->addButton("Set Home", -1, "", 'sethome');
                $form->addButton("Delete Home", -1, "", 'delhome');
            }
        }
        $player->sendForm($form);
    }

    public function selectGeneralCommand(Player $player, ?string $selection) {
        if($selection == null) {
            return;
        }
        switch($selection) {
            case "top":
                break;

            case "info":
                $facName = $this->plugin->getPlayer($player)->getFaction();
                $this->sendFactionInfoForm($player, $facName);
                break;

            case "members":
                $facName = $this->plugin->getPlayer($player)->getFaction();
                $this->sendFactionMembersForm($player, $facName);
                break;

            case "setmotd":
                $p = $this->plugin->getPlayer($player);
                $facName = $p->getFaction();
                if($this->isElite($p->getFactionRank())) {
                    if($this->factionExists($facName)) {
                        $faction = $this->getFaction($facName);
                        $currentMOTD = $faction->getMOTD();
                        $form = new CustomForm([$this, 'processMOTDChange']);
                        $form->setTitle('Arcane Faction Manager');
                        $form->addInput("Change Message of the Day", $currentMOTD, $currentMOTD);
                        $player->sendForm($form);
                    }
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . "You have insufficient permissions to set message of the day.");
                    $player->sendMessage($this->plugin->prefixDos . "You must be at least an commander.");
                }
                break;

            case "home":
                $facName = $this->plugin->getPlayer($player)->getFaction();
                if($this->factionExists($facName)) {
                    $faction = $this->getFaction($facName);
                    if($faction->homeExists()) {
                        $home = $faction->getHome();
                        $player->teleport($home);
                        $player->sendMessage($this->plugin->prefix . "You have teleported to your faction home.");
                    }
                    else {
                        $player->sendMessage($this->plugin->prefixDos . "Faction home doesn't exist.");
                    }
                }
                break;

            case "sethome":
                $facName = $this->plugin->getPlayer($player)->getFaction();
                if($this->factionExists($facName)) {
                    $faction = $this->getFaction($facName);
                    $faction->setHome($player->getPosition());
                    $player->sendMessage($this->plugin->prefix . "Faction home set.");
                }
                break;

            case "delhome":
                $facName = $this->plugin->getPlayer($player)->getFaction();
                if($this->factionExists($facName)) {
                    $faction = $this->getFaction($facName);
                    if($faction->homeExists()) {
                        $faction->delHome();
                        $player->sendMessage($this->plugin->prefix . "Faction home deleted.");
                    }
                    else {
                        $player->sendMessage($this->plugin->prefixDos . "Faction home doesn't exist.");
                    }
                }
                break;
        }
    }

    // Member Manager UI's

    public function createMemberManagerUI(Player $player): void {
        $form = new SimpleForm([$this, 'selectMMCommand']);
        $form->setTitle("Arcane Faction Manager");
        $p = $this->plugin->getPlayer($player);
        if($p->hasFaction()) {
            if($this->isElite($p->getFactionRank())) {
                $form->addButton("Invite Player", -1, "", 'invite');
                $form->addButton("Kick Player", -1, "", 'kick');
            }
        }
        $player->sendForm($form);
    }

    public function selectMMCommand(Player $player, ?string $selection) {
        if($selection == null) {
            return;
        }
        switch($selection) {
            case "invite":
                $p = $this->plugin->getPlayer($player);
                $facName = $p->getFaction();
                if($this->isElite($p->getFactionRank())) {
                    if($this->factionExists($facName)) {
                        $form = new CustomForm([$this, 'processPlayerInvite']);
                        $form->setTitle('Arcane Faction Manager');
                        $form->addLabel("Select a player to invite.");
                        $form->addInput("Enter a name", 'name', 'Steve', 'username');
                        $player->sendForm($form);
                    }
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . "You have insufficient permissions to invite a player.");
                    $player->sendMessage($this->plugin->prefixDos . "You must be at least an officer.");
                }
                break;

            case "kick":
                $p = $this->plugin->getPlayer($player);
                $facName = $p->getFaction();
                if($this->isElite($p->getFactionRank())) {
                    if($this->factionExists($facName)) {
                        $faction = $this->getFaction($facName);
                        if(count($faction->getMembers()) <= 1) {
                            $player->sendMessage($this->plugin->prefixDos . "Your faction has no other members.");
                        }
                        else {
                            $form = new SimpleForm([$this, 'processPlayerKick']);
                            $form->setTitle('Arcane Faction Manager');
                            $form->setContent("Select a player to kick.");
                            if($p->getFactionRank() == "commander") {
                                $members = $faction->getMembers();
                                unset($members[$player->getName()]);
                                foreach ($members as $u => $r) {
                                    if($r !== "commander") {
                                        $full = strtoupper($u) . " - " . $this->getRankColor($r) . ucfirst($r);
                                        $form->addButton($full, -1, "", $u);
                                    }
                                }
                            }
                            if($p->isFactionLeader()) {
                                $members = $faction->getMembers();
                                unset($members[$player->getName()]);
                                foreach ($members as $u => $r) {
                                    if($r !== "leader") {
                                        $full = strtoupper($u) . " - " . $this->getRankColor($r) . ucfirst($r);
                                        $form->addButton($full, -1, "", $u);
                                    }
                                }
                            }
                            $player->sendForm($form);
                        }
                    }
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . "You have insufficient permissions to kick a player.");
                    $player->sendMessage($this->plugin->prefixDos . "You must be at least an commander.");
                }
                break;

        }
    }

    // Leader Control Panel UI's

    // TODO: CODE LEADER FUNCTIONS
    public function createLeaderUI(Player $player): void {
        $form = new SimpleForm(null);
        $form->setTitle("Arcane Faction Manager");
        $p = $this->plugin->getPlayer($player);
        if($p->hasFaction()) {
            if($p->isFactionLeader()) {
                $form->addButton("Add Ally", -1, "", 'add_ally');
                $form->addButton("Remove Ally", -1, "", 'rm_ally');
                $form->addButton("Add Enemy", -1, "", 'add_enemy');
                $form->addButton("Remove Enemy", -1, "", 'rm_enemy');
                $form->addButton("Change Leader", -1, "", 'setleader');
                $form->addButton(TextFormat::RED . "Delete Faction", -1, "", 'deletefac');
            }
        }
        $player->sendForm($form);
    }

    public function selectLeaderCommand(Player $player, ?string $selection) {
        if($selection == null) {
            return;
        }
        switch($selection) {

            case "add_ally":
                $p = $this->plugin->getPlayer($player);
                $facName = $p->getFaction();
                if($p->isFactionLeader()) {
                    if($this->factionExists($facName)) {
                        if(count($this->faction) <= 1) {
                            $player->sendMessage($this->plugin->prefixDos . "There are currently no other factions that exist.");
                        }
                        else {
                            $form = new SimpleForm([$this, 'selectAllyTool']);
                            $form->setTitle('Arcane Faction Manager');
                            $form->setContent("Select an option to help begin an alliance.");
                            $form->addButton("Search for Faction", -1, "", "search");
                            $form->addButton("Top Faction List", -1, "", "top");
                            $player->sendForm($form);
                        }
                    }
                }
                else {
                    $player->sendMessage($this->plugin->prefixDos . "You have insufficient permissions to kick a player.");
                    $player->sendMessage($this->plugin->prefixDos . "You must be at least an commander.");
                }
                break;
        }
    }

    public function selectAllyTool(Player $player, ?string $option) {
        if($option == null) {
            return;
        }

        if($option == "top") {
            return;
        }
        else {
            $this->createAllySearchUI($player);
        }
    }

    public function createAllySearchUI(Player $player) {
        $form = new CustomForm([$this, 'processAllySearch']);
        $form->setTitle("Arcane Faction Manager");
        $form->addInput("Enter in faction to request alliance", "Faction Name");
        $player->sendForm($player);
    }

    public function processAllySearch(Player $player, ?array $data) {
        if($data == null) {
            return;
        }
        $name = str_replace(" ", "", $data[0]);
        if(strlen($name) < 3) {
            $player->sendMessage($this->plugin->prefixDos . "Faction names must be at least 3 characters long.");
            return;
        }
        if(strlen($name) > 13) {
            $player->sendMessage($this->plugin->prefixDos . "Faction names must contain no more than characters");
            return;
        }
        if(!$this->alphanum($name)) {
            $player->sendMessage($this->plugin->prefixDos . "Faction names cannot contain any special characters.");
            return;
        }
        $p = $this->plugin->getPlayer($player);
        if(strtolower($p->getFaction()) == strtolower($name)) {
            $player->sendMessage($this->plugin->prefixDos . "You cannot select your own faction.");
            return;
        }
        if($this->factionExists($name)) {
            $this->sendAllianceRequest($player, $name);
            return;
        }
        else {
            $player->sendMessage($this->plugin->prefixDos . "Faction with the name: " . $name . " doesn't exist.");
            return;
        }
    }

    public function sendAllianceRequest(Player $player, $name) {
        if($this->factionExists($name)) {
            $faction = $this->getFaction($name);
            if(!$faction->isAlly($name)) {
                $faction = $this->plugin->getPlayer($player)->getFaction();
                
                $this->askForAlliance($faction, $name);
            }
        }
        else {
            $player->sendMessage($this->plugin->prefixDos . "Faction with the name: " . $name . " doesn't exist.");
            return;
        }
    }

    public function askForAlliance($factionName, $allianceFactionName){
        if($this->factionExists($factionName) && $this->factionExists($allianceFactionName)) {
            $faction = $this->getFaction($factionName);
            $factionLeader = $faction->getLeader();
            $allianceFaction = $this->getFaction($factionName);
            $allianceLeader = $allianceFaction->getLeader();
            $leaderInstance = $this->server->getPlayerExact($allianceLeader);
            if($leaderInstance instanceof Player) {
                $form = new SimpleForm([$this, 'processAllianceRequest']);
                $form->setTitle("Faction Alliance Request");
                $form->setContent($faction->getName() . " desires to form an alliance with your faction.");
                $form->setContent("Click 'Accept Alliance' or 'Deny Alliance' to respond.");
                $form->addButton(TextFormat::GREEN . "Accept Alliance", -1, "", "accept");
                $form->addButton(TextFormat::RED . "Deny Alliance", -1, "", "deny");
                $form->addButton("View Faction Info", -1, "", "info");
                $form->setContent("/f accept and /f deny are also available.");
                $leaderInstance->sendForm($form);
            }
            else {
                $leader = $this->server->getPlayerExact($factionLeader);
                if($leader instanceof Player) {
                    $leader->sendMessage($this->plugin->prefixTres . $allianceFaction->getName() . "'s leader is currently not online.");
                    $leader->sendMessage("They will be notified of your alliance request when they're online!");
                }
                $this->saveAllianceRequest($allianceLeader, $faction->getName());
            }
        }
    }

    public function saveAllianceRequest($allianceLeader, $faction) {
        $cfg = new Config($this->plugin->getDataFolder() . "allyrequest.json", Config::YAML);
        $cfg->set($allianceLeader, $faction);
        $cfg->save();
    }

    public function getAllyInvite($username) {
        $username = strtolower($username);

        if(isset($this->allyInvite[$username])) {
            return $this->allyInvite[$username];
        }
        return false;
    }

    public function isAllyInvited($username) {
        $username = strtolower($username);

        if(isset($this->allyInvite[$username])) {
            return true;
        }
        return false;
    }

    public function addAllyInvite($senderUsername, $username, $factionName) {
        $username = strtolower($username);
        $senderUsername = strtolower($senderUsername);

        if(!isset($this->allyInvite[$username])) {
            $data = [$factionName, $senderUsername];
            $this->allyInvite[$username] = $data;
        }
    }

    public function rmAllyInvite($username) {
        $username = strtolower($username);

        if(isset($this->allyInvite[$username])) {
            unset($this->allyInvite[$username]);
        }
    }

    public function processAllianceRequest(Player $player, ?string $response) {
        if($response == null) {
            return;
        }
        if(!$this->isInvited($player->getName())) {
            $player->sendMessage($this->plugin->prefixDos . "Your faction doesn't have an alliance request.");
            return;
        }
        if($response == "accept") {
            $factionName = $this->getInvite($player->getName())[0];
            $senderUsername = $this->getInvite($player->getName())[1];
            $sender = $this->server->getPlayerExact($senderUsername);
            $this->invitePlayer($sender, $player, $factionName);
            return;
        }
        if($response == "deny") {
            $senderUsername = $this->getInvite($player->getName())[1];
            $sender = $this->server->getPlayerExact($senderUsername);
            if($sender instanceof Player) {
                $sender->sendMessage($this->plugin->prefixDos . $player->getName() . " has denied your invite.");
            }
            $this->rmInvite($player->getName());
            $player->sendMessage($this->plugin->prefixTres . "You have denied " . $senderUsername . "'s invite.");
            return;
        }
        if($response == "info") {
            $factionName = $this->getInvite($player->getName())[0];
            $this->sendFactionInfoForm($player, $factionName);
        }
    }

    public function invitePlayer(?Player $sender, Player $invitee, $factionName) {
        if($this->factionExists($factionName)) {
            $faction = $this->getFaction($factionName);
            $faction->setMember($invitee->getName(), 'member');
            $p = $this->plugin->getPlayer($invitee);
            $p->setFaction($factionName);
            $p->setFactionRank("member");
            $p->setFactionChatFormat();
            $this->rmInvite($invitee->getName());
            $invitee->sendMessage($this->plugin->prefix . "You have been successfully invited to " . TextFormat::RED . $faction->getName() . "!");
            if($sender instanceof Player) $sender->sendMessage($this->plugin->prefix . $invitee->getName() . " has been invited to " . TextFormat::RED . $faction->getName() . "!");
        }
    }

    // Faction Creation UI's

    public function createNewFactionUI(Player $player): void {
        $form = new CustomForm([$this, 'processFactionName']);
        $form->setTitle('Create Faction');
        $form->addInput("Enter a name", "Faction Name");
        $form->addLabel("Select name wisely. You cannot rename your faction.");
        $player->sendForm($form);
    }

    public function alphanum($string){
        if(function_exists('ctype_alnum')){
            $return = ctype_alnum($string);
        }else{
            $return = preg_match('/^[a-z0-9 ]+$/i', $string) > 0;
        }
        return $return;
    }

    public function alphanumMOTD($string){
        return preg_match('/^[a-z0-9§ ]+$/i', $string) > 0;
    }

    public function processFactionName(Player $player, ?array $data) {
        if($data == null) {
            return;
        }
        $name = str_replace(" ", "", $data[0]);
        if(strlen($name) < 3) {
            $player->sendMessage($this->plugin->prefixDos . "Faction name must be at least 3 characters long.");
            return;
        }
        if(strlen($name) > 13) {
            $player->sendMessage($this->plugin->prefixDos . "Faction name must contain no more than characters");
            return;
        }
        if(!$this->alphanum($name)) {
            $player->sendMessage($this->plugin->prefixDos . "Faction name cannot contain any special characters.");
            return;
        }
        if($this->factionExists($name)) {
            $player->sendMessage($this->plugin->prefixDos . "Faction with this name is already taken.");
            return;
        }
        if($this->plugin->getPlayer($player)->hasFaction()) {
            $player->sendMessage($this->plugin->prefixDos. "You must leave your current faction to create one.");
            return;
        }
        $this->createNewFaction($name, $player->getName());
        $player->sendMessage($this->plugin->prefix . "Creating faction...");
    }
}