<?php 
	// Setting JSON to be HTTP Header format
	header('Content-Type: application/json');
	require "../../includes/db.php";
	require "../../includes/functions.php";
	
	$method = $_SERVER['REQUEST_METHOD'];
	
	switch ($method) {

		case 'GET';                                         		// GET REQUESTS
			
			// Search Nearby Vendors
			if(ISSET($_GET['url']) && $_GET['url']=="search"){
			
				//INPUT
				$radius = $_GET['radius'];
				
				if(ISSET($_GET['userLAT']) && ISSET($_GET['userLNG'])) {
					
					$userLAT = floatval($_GET['userLAT']);
					$userLNG = floatval($_GET['userLNG']);
					
				} elseif(ISSET($_GET['userAddress'])){
					
					$userAddress = $_GET['userAddress'];
					$userLocation = getCoordinates($userAddress);		//getCoordinates in fuctions.php

					$userLAT = floatval($userLocation[0]);
					$userLNG = floatval($userLocation[1]);
				}
		
				$maxLAT = $userLAT + ($radius/69);
				$minLAT = $userLAT - ($radius/69);
		
				$maxLNG = $userLNG + ($radius/69);
				$minLNG = $userLNG - ($radius/69);
		
				try {
					//Get DB class
					$pdo = new db();									//db class in db.php
	
					//Connect to db
					$pdo = $pdo->connect();								//connect fuction in db.php
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
					$sql = "SELECT * FROM Vendors 
							WHERE vendorLAT >= :minLAT 
							AND vendorLAT <= :maxLAT 
							AND vendorLNG >= :minLNG
							AND vendorLNG <= :maxLNG
							ORDER BY vendorName"; 
		
		
					$query = $pdo->prepare($sql);
		
					$query->bindParam(':minLAT', $minLAT, PDO::PARAM_STR);
					$query->bindParam(':maxLAT', $maxLAT, PDO::PARAM_STR);
					$query->bindParam(':minLNG', $minLNG, PDO::PARAM_STR);
					$query->bindParam(':maxLNG', $maxLNG, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
		
					if(empty($row_count))
					{
						echo '{message: "No restaurants nearby", count: 0}';
					} else{
			
						$resultArray = array();
						$tempArray = array();
						
						$i=1;
			
						while ($row = $query->fetch()){
							
							$vendorLAT = floatval($row->vendorLAT);
							$vendorLNG = floatval($row->vendorLNG);
				
							$distance = getDistance($userLAT,$userLNG,$vendorLAT,$vendorLNG);				//getDistance in functions.php
							$distance = round($distance, 1);
		
							$row->distance=$distance;
							$row->index=$i;
							$i=$i+1;
							
							// Removing Passwords from results
							unset($row->vendorPassword); 						
							
							$row->vendorImageURL = str_replace("\/","/",$row->vendorImageURL);
				
							//Array for json
							$tempArray = $row;
							array_push($resultArray, $tempArray);
		
						}
			
						$result = array('count'=>$row_count, 'results'=>$resultArray);
						$result = json_encode($result, JSON_PRETTY_PRINT);
						echo $result;
					}
	
				} catch(PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} 
			
			// Get Vendor info
			elseif(ISSET($_GET['url']) && $_GET['url']=="get-vendor") {
			
				$vendorEmail = $_GET['vendorEmail'];
		
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT * FROM Vendors WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
						
				
					if (empty($row_count)){
						echo '{message: "Vendor not found"}';
					} else {
					
						$resultArray = $query->fetch();
					
						// Removing passwords from result array
						unset($resultArray[0]['vendorPassword']);	
					
						$result = array('results'=>$resultArray);
						$result = json_encode($result, JSON_PRETTY_PRINT);
				
						echo $result;
				
						http_response_code(200);
					
					}
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} else {
				echo '{message: {text: "Invalid Request!"}}';
				http_response_code(405);
			}	
			
		break;

		
		
		case 'POST';                                                	// POST REQUESTS
			$data = file_get_contents('php://input');
			$data = json_decode($data, true);
			
			// Register new Vendor
			if(ISSET($_GET['url']) && $_GET['url']=="register"){								// REGISTRATION
		
				$vendorName = $data['vendorName'];
				$vendorEmail = $data['vendorEmail'];
				$vendorPassword = $data['vendorPassword'];
				$vendorContactNumber = $data['vendorContactNumber'];
				$vendorAddress = $data['vendorAddress'];
				$vendorCuisine = $data['vendorCuisine'];
				$vendorLAT = $data['vendorLAT'];
				$vendorLNG = $data['vendorLNG'];
				
				$hashedPassword = password_hash($vendorPassword, PASSWORD_BCRYPT);
				
				// Check if email already registered
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT vendorEmail FROM Vendors WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
					$resultArray = $query->fetch(PDO::FETCH_ASSOC);
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}	
					
				if(!ISSET($resultArray['vendorEmail'])){	
					try {
						//Get db class
						$pdo = new db();
				
						//Connect to db
						$pdo = $pdo->connect();
				
						$sql = "INSERT INTO Vendors(vendorName, vendorEmail, vendorPassword, vendorLAT, vendorLNG, vendorContactNumber, vendorAddress, vendorCuisine)
								VALUES(:vendorName, :vendorEmail, :vendorPassword, :vendorLAT, :vendorLNG, :vendorContactNumber, :vendorAddress, :vendorCuisine)";
				
						$query = $pdo->prepare($sql);
				
						$query->bindParam(':vendorName', $vendorName, PDO::PARAM_STR);
						$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
						$query->bindParam(':vendorPassword', $hashedPassword, PDO::PARAM_STR);
						$query->bindParam(':vendorLAT', $vendorLAT, PDO::PARAM_STR);
						$query->bindParam(':vendorLNG', $vendorLNG, PDO::PARAM_STR);
						$query->bindParam(':vendorContactNumber', $vendorContactNumber, PDO::PARAM_STR);
						$query->bindParam(':vendorAddress', $vendorAddress, PDO::PARAM_STR);
						$query->bindParam(':vendorCuisine', $vendorCuisine, PDO::PARAM_STR);
				
						$query->execute();
				
						if (!$query){
							echo '{error: {text: ' . $e->getMessage() . '}}';
						} else {
							echo '{message: {text: "Vendor Added!"}}';
							http_response_code(200);
						}
				
					} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
					}
				} else {
					echo '{error: {text: "Email address already registered!"}}';
				}
			} 
			
			// Login Vendor
			elseif(ISSET($_GET['url']) && $_GET['url']=="auth"){								// LOGIN			
				
				$vendorEmail = $data['vendorEmail'];
				$vendorPassword = $data['vendorPassword'];
				
				try {
					//Get db class
					$pdo = new db();
					
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT vendorEmail, vendorPassword FROM Vendors WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
					
					if (!$query){ 
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						$resultArray = $query->fetchAll();
						
						// Verify whether email address exists in DB
						if(!empty($resultArray)){
						
							$hashedPassword = $resultArray[0]['vendorPassword'];
							
							// Verify hash stored in DB against password entered
							if(password_verify($vendorPassword, $hashedPassword)){
		
								// Check if a newer hashing algorithm is available
								if(password_needs_rehash($hashedPassword, PASSWORD_BCRYPT)){
								
									$newHash = password_hash($vendorPassword, PASSWORD_BCRYPT);
							
									// Update DB with the new hashed password
									try {
										//Get db class
										$pdo = new db();
				
										//Connect to db
										$pdo = $pdo->connect();
				
										$sql = "UPDATE Vendors SET vendorPassword=:vendorPassword 
											WHERE vendorEmail = :vendorEmail";
					
										$query = $pdo->prepare($sql);
										$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
										$query->bindParam(':vendorPassword', $newHash, PDO::PARAM_STR);
										$query->execute();
								
									} catch (PDOException $e){
										echo '{error: {text: ' . $e->getMessage() . '}}';
									}
								}
							
								// LOGIN
								
								$crypto_strong = "True";
								$token = bin2hex(openssl_random_pseudo_bytes(64, $crypto_strong));
								
								$hashedToken = sha1($token);
								
								try {
									//Get db class
									$pdo = new db();
						
									//Connect to db
									$pdo = $pdo->connect();
				
									$sql = "INSERT INTO login_tokens(id, token, email) VALUES('',:token, :email)";
									
									$query = $pdo->prepare($sql);
									$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
									$query->bindParam(':email', $vendorEmail, PDO::PARAM_STR);
									$query->execute();
				
									if (!$query){
										echo '{error: {text: ' . $e->getMessage() . '}}';
									} else {
										echo '{message: {text: "Logged in!"}, token: "'.$token.'"}';
										http_response_code(200);
									}
				
								} catch (PDOException $e){
									echo '{error: {text: ' . $e->getMessage() . '}}';
								}
							
							} else {
								echo '{error: {text: "Invalid Password!"}}';
							}
						} else {
							echo '{error: {text: "Email address not registered!"}}';
						}							
					}
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} else {
				echo '{message: {text: "Invalid Request!"}}';
				http_response_code(405);
			}		
		break;

		
		
		case 'PUT';                                                  		// PUT REQUESTS
			
			//Upadte Vendor info
			if (ISSET($_GET['url']) && $_GET['url']=="update-account"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$vendorName = $data['vendorName'];
				$vendorEmail = $data['vendorEmail'];
				$vendorContactNumber = $data['vendorContactNumber'];
				$vendorAddress = $data['vendorAddress'];
				$vendorCuisine = $data['vendorCuisine'];
			
				$vendorLocation = getCoordinates($vendorAddress);				//getCoordinates in fuctions.php
				$vendorLAT = floatval($vendorLocation[0]);
				$vendorLNG = floatval($vendorLocation[1]);
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "UPDATE Vendors SET vendorName=:vendorName, vendorLAT=:vendorLAT, vendorLNG=:vendorLNG, 
							vendorContactNumber=:vendorContactNumber, vendorAddress=:vendorAddress, vendorCuisine=:vendorCuisine 
							WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':vendorName', $vendorName, PDO::PARAM_STR);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->bindParam(':vendorLAT', $vendorLAT, PDO::PARAM_STR);
					$query->bindParam(':vendorLNG', $vendorLNG, PDO::PARAM_STR);
					$query->bindParam(':vendorContactNumber', $vendorContactNumber, PDO::PARAM_STR);
					$query->bindParam(':vendorAddress', $vendorAddress, PDO::PARAM_STR);
					$query->bindParam(':vendorCuisine', $vendorCuisine, PDO::PARAM_STR);
				
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Account updated!"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} 
			
			// Update new Password
			elseif(ISSET($_GET['url']) && $_GET['url']=="update-password"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$vendorEmail = $data['vendorEmail'];
				$currentPassword = $data['currentPassword'];
				$newPassword = $data['newPassword'];
				
				try {
					//Get db class
					$pdo = new db();
					
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT vendorEmail, vendorPassword FROM Vendors WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
					
					if (!$query){ 
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						$resultArray = $query->fetchAll();
						
						// Verify whether email address exists in DB
						if(!empty($resultArray)){
						
							$hashedPassword = $resultArray[0]['vendorPassword'];
							
							// Verify hash stored in DB against password entered
							if(password_verify($currentPassword, $hashedPassword)){
								
								// Encryot newPassword
								$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
							
								// Update DB with the new hashed password
								try {
									//Get db class
									$pdo = new db();
				
									//Connect to db
									$pdo = $pdo->connect();
				
									$sql = "UPDATE Vendors SET vendorPassword=:vendorPassword 
										WHERE vendorEmail = :vendorEmail";
				
									$query = $pdo->prepare($sql);
									$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
									$query->bindParam(':vendorPassword', $newHash, PDO::PARAM_STR);
									$query->execute();
								
								} catch (PDOException $e){
									echo '{error: {text: ' . $e->getMessage() . '}}';
								}
							} else {
								echo '{error: {text: "Invalid Password!"}}';
							}
						} else {
							echo '{error: {text: "Email address not registered!"}}';
						}							
					}
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}	
			} else {
				echo '{message: {text: "Invalid Request!"}}';
				http_response_code(405);
			}	
		break;

		
		
		case 'DELETE'; 
                                               		 // DELETE REQUESTS
			// LOGOUT
			if(ISSET($_GET['url']) && $_GET['url']=='logout'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
				
				$token = $data['token'];
				$hashedToken = sha1($token);
				
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM login_tokens WHERE token = :token";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Vendor successfully logged out!"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{error: {text: ' . $e->getMessage() . '}}';
				};			
			} 
			
			// Delete Vendor Account
			elseif(ISSET($_GET['url']) && $_GET['url']=='delete-account'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
			
				$vendorEmail = $data['vendorEmail'];
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM Vendors WHERE vendorEmail = :vendorEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Vendor Deleted!"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{error: {text: ' . $e->getMessage() . '}}';
				};	
			} else {
				echo '{message: {text: "Invalid Request!"}}';
				http_response_code(405);
			}	
		break;

		
		
		default;
			echo '{message: {text: "Invalid Request!"}}';
			http_response_code(405);
		break;
	}
	
?>

