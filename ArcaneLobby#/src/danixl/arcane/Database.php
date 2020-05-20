<?php

namespace danixl\arcane;

use mysqli;

class Database {
 
     private $db;

     public function __construct(Main $plugin, $mySQL) {
         $this->plugin = $plugin;
         $this->mySQL = $mySQL;
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
             username varchar(16) NOT NULL, rank_p VARCHAR(10), rank_s VARCHAR(10), coins int NOT NULL, deaths int NOT NULL, kills int NOT NULL)");
             $this->plugin->getLogger()->info("§aSuccesfully connected to database!");
         }
         else {
             $this->plugin->getLogger()->critical("Could not connect to database...");
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