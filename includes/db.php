<?php 

	require "config.php";
	
	class DB {
	
		private $host = DB_HOST;
		private $username = DB_USER;
		private $password = DB_PASSWORD;
		private $dbname = DB_NAME;
		
		public function connect(){
			
				$mysql_str = "mysql:host=$this->host; dbname=$this->dbname";
				$pdo = new PDO($mysql_str, $this->username, $this->password);
				$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
				
				return $pdo;
		}

	}

?>

