<?php

namespace danixl\fac;

use mysqli;

class Database {
 
     private $plugin, $mySQL, $factionMySQL, $db;

     public function __construct(Main $plugin) {
         $this->plugin = $plugin;
         $this->mySQL = [
             'hostname' => 'play.arcn.us',
             'username' => 'arcane_dg',
             'password' => 'Dannyx0308@',
             'database' => 'arcane_netw'
         ];
         $this->factionMySQL = [
             'hostname' => '127.0.0.1',
             'username' => 'arcane_fac',
             'password' => 'Dannyx0308@',
             'database' => 'arcane_netw'
         ];
     }
          
     public function loadDatabase() {
         $hostname = $this->mySQL["hostname"];
         $username = $this->mySQL["username"];
         $password = $this->mySQL["password"];
         $port = 3306;
         $database = $this->mySQL["database"];
         
         $this->db = @new mysqli($hostname, $username, $password, $database, $port);
         
         if(!$this->db->connect_error) {
             $this->db->query("CREATE TABLE IF NOT EXISTS arcane_players (
             username varchar(16) NOT NULL, rank_p VARCHAR(10), rank_s VARCHAR(10), coins int NOT NULL, deaths int NOT NULL, kills int NOT NULL, faction varchar(20))");
             $this->plugin->getLogger()->info("§aSuccessfully connected to main database!");
         }
         else {
             $this->plugin->getLogger()->critical("Could not connect to main database...");
             $this->plugin->getLogger()->critical("MySQL: " . $this->db->connect_error);
         }
     }

    public function loadFactionDatabase() {
        $hostname = $this->factionMySQL["hostname"];
        $username = $this->factionMySQL["username"];
        $password = $this->factionMySQL["password"];
        $port = 3306;
        $database = $this->factionMySQL["database"];

        $this->db = @new mysqli($hostname, $username, $password, $database, $port);

        if(!$this->db->connect_error) {
            $this->db->query("CREATE TABLE IF NOT EXISTS factions (
             faction varchar(20) NOT NULL, name varchar(20) NOT NULL, tier VARCHAR(20), leader VARCHAR(20) NOT NULL, allies JSON, enemies JSON, motd TEXT, coins int NOT NULL, power int NOT NULL, home TEXT, members JSON)");
            // Faction claims must always be local
            $this->db->query("CREATE TABLE IF NOT EXISTS faction_claims (
             faction varchar(20) NOT NULL, level VARCHAR(30), chunkx int NOT NULL, chunkz int NOT NULL)");
            $this->plugin->getLogger()->info("§bSuccessfully connected to faction database!");
        }
        else {
            $this->plugin->getLogger()->critical("Could not connect to faction database...");
            $this->plugin->getLogger()->critical("MySQL: " . $this->db->connect_error);
        }
    }
     
     public function closeDatabase() {
         if($this->db && !$this->db->connect_error) $this->db->close();
     }
     
     public function getQuery($query) {
         $query = $this->db->query($query);
         if(!$query) {
             return false;
         }
         else {
             return true;
         }
     }
}