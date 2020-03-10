<?php 
	// Setting JSON to be HTTP Header format
	header('Content-Type: application/json');
	require "../../includes/db.php";
	require "../../includes/functions.php";
	
	$method = $_SERVER['REQUEST_METHOD'];
	
	switch ($method) {

		case 'GET';                                         		// GET REQUESTS
				
			// Get Customer info
			if(ISSET($_GET['url']) && $_GET['url']=="get-customer") {
			
				$customerEmail = $_GET['customerEmail'];
		
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT * FROM Customers WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					$resultArray = $query->fetchAll();
					unset($resultArray[0]['customerPassword']);			// Removing passwords from result array
					
					if (empty($resultArray)){
						echo '{message: "Error fetching data! Invalid email address!", status: "failed"}';
					} else {
	
					$result = array('results'=>$resultArray);
					$result = json_encode($result, JSON_PRETTY_PRINT);
				
					echo $result;
				
					http_response_code(200);
					
					}
				
				} catch (PDOException $e){
					echo '{message: "' . $e->getMessage() . '", status: "failed"}';
				}
			} else {
				echo '{message: "Invalid Request!", status: "failed"}';
				http_response_code(405);
			}	
			
		break;

		
		
		case 'POST';                                                	// POST REQUESTS
			$data = file_get_contents('php://input');
			$data = json_decode($data, true);
			
			// Register new Customer
			if(ISSET($_GET['url']) && $_GET['url']=="register"){								// REGISTRATION
		
				$customerName = $data['customerName'];
				$customerEmail = $data['customerEmail'];
				$customerPassword = $data['customerPassword'];
				$customerContactNumber = $data['customerContactNumber'];
				$customerAddress = "";
				
				$hashedPassword = password_hash($customerPassword, PASSWORD_BCRYPT);
				
				// Check if email already registered
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT customerEmail FROM Customers WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					$resultArray = $query->fetch(PDO::FETCH_ASSOC);
				
				} catch (PDOException $e){
					echo '{message: "blah ' . $e->getMessage() . '", status: "failed"}';
				}	
					
				if(!ISSET($resultArray['customerEmail'])){	
					try {
						//Get db class
						$pdo = new db();
				
						//Connect to db
						$pdo = $pdo->connect();
				
						$sql = "INSERT INTO Customers(customerName, customerEmail, customerPassword, customerContactNumber, customerAddress)
								VALUES(:customerName, :customerEmail, :customerPassword, :customerContactNumber, :customerAddress)";
				
						$query = $pdo->prepare($sql);
				
						$query->bindParam(':customerName', $customerName, PDO::PARAM_STR);
						$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
						$query->bindParam(':customerPassword', $hashedPassword, PDO::PARAM_STR);
						$query->bindParam(':customerContactNumber', $customerContactNumber, PDO::PARAM_STR);
						$query->bindParam(':customerAddress', $customerAddress, PDO::PARAM_STR);
						$query->execute();
						$row_count = $query->rowCount();
				
						if (empty($row_count)){
							echo '{message: "Error creating new account!", status: "failed"}';
						} else {
							// Generate token
								
								$crypto_strong = "True";
								$token = bin2hex(openssl_random_pseudo_bytes(64, $crypto_strong));
								
								$hashedToken = sha1($token);
								
								try {
									//Get db class
									$pdo = new db();
						
									//Connect to db
									$pdo = $pdo->connect();
									
									// Insert token in db
									$sql = "INSERT INTO login_tokens(id, token, email) VALUES('',:token, :email)";
									
									$query = $pdo->prepare($sql);
									$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
									$query->bindParam(':email', $customerEmail, PDO::PARAM_STR);
									$query->execute();
									$created = $query->rowCount();
				
									if (empty($created)){
										echo '{message: "Error creating new user!", status: "failed"}';
									} else {
										// Request successful! Send token to app
										echo '{message: "Welcome '.$customerName.'!", authToken: "'.$token.'", status: "succeeded"}';
										http_response_code(200);
									}
				
								} catch (PDOException $e){
									echo '{message: "'.$e->getMessage().'", status: "failed"}';
								}
						}
				
					} catch (PDOException $e){
					echo '{message: "' . $e->getMessage() . '", status: "failed"}';
					}
				} else {
					echo '{message: "Email address already registered!", status: "failed"}';
				}
			} 
			
			// LOGIN User using email and password
			elseif(ISSET($_GET['url']) && $_GET['url']=="auth"){								// LOGIN			
				
				$customerEmail = $data['customerEmail'];
				$customerPassword = $data['customerPassword'];
				
				try {
					//Get db class
					$pdo = new db();
					
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT customerPassword, customerName FROM Customers WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$status = $query->execute();
					
					if (!$status){ 
						echo '{message: ' . $e->getMessage() . ', status: "failed"}';
					} else {
						$resultArray = $query->fetchAll();
						
						// Verify whether email address exists in DB
						if(!empty($resultArray)){
							
							$customerName = $resultArray[0]['customerName'];
							$hashedPassword = $resultArray[0]['customerPassword'];
							
							// Verify hash stored in DB against password entered
							if(password_verify($customerPassword, $hashedPassword)){
		
								// Check if a newer hashing algorithm is available
								if(password_needs_rehash($hashedPassword, PASSWORD_BCRYPT)){
								
									$newHash = password_hash($customerPassword, PASSWORD_BCRYPT);
							
									// Update DB with the new hashed password
									try {
										//Get db class
										$pdo = new db();
				
										//Connect to db
										$pdo = $pdo->connect();
				
										$sql = "UPDATE Customers SET customerPassword = :customerPassword 
												WHERE customerEmail = :customerEmail";
					
										$query = $pdo->prepare($sql);
										$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
										$query->bindParam(':customerPassword', $newHash, PDO::PARAM_STR);
										$query->execute();
								
									} catch (PDOException $e){
										echo '{message: "' . $e->getMessage() . '", status: "failed"}';
									}
								}
							
								// Generate token
								$crypto_strong = "True";
								$token = bin2hex(openssl_random_pseudo_bytes(64, $crypto_strong));
								
								$hashedToken = sha1($token);
								
								try {
									//Get db class
									$pdo = new db();
						
									//Connect to db
									$pdo = $pdo->connect();
				
									// Insert token in db
									$sql = "INSERT INTO login_tokens(id, token, email) VALUES('',:token, :email)";
									
									$query = $pdo->prepare($sql);
									$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
									$query->bindParam(':email', $customerEmail, PDO::PARAM_STR);
									$query->execute();
									$created = $query->rowCount();
				
									if (empty($created)){
										echo '{message: "Error logging in!", status: "failed"}';
									} else {
										// Request successful! Send token to app
										echo '{message: "Welcome '.$customerName.'!", authToken: "'.$token.'", status: "succeeded"}';
										http_response_code(200);
									}
				
								} catch (PDOException $e){
									echo '{message: "'.$e->getMessage().'", status: "failed"}';
								}
							
							} else {
								echo '{message: "Invalid Password!", status: "failed"}';
							}
						} else {
							echo '{message: "Email address not registered!", status: "failed"}';
						}							
					}
				
				} catch (PDOException $e){
					echo '{message: "'.$e->getMessage().'", status: "failed"}';
				}
			}
			
			// LOGIN User using authentication token
			elseif(ISSET($_GET['url']) && $_GET['url']=='login'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
				
				$authToken = $data['authToken'];
				$customerEmail = $data['customerEmail'];
				$hashedToken = sha1($authToken);
				
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "SELECT email FROM login_tokens WHERE token = :token";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
					$status = $query->execute();
					
					if (!$status){ 
						echo '{message: "' . $e->getMessage() . '", status: "failed"}';
					} else {
						$resultArray = $query->fetchAll();
						
						if (empty($resultArray)){
							echo '{message: "User not logged in!" status: "failed"}';
						} else {
							$email = $resultArray[0]['email'];
							
							if ($email==$customerEmail){
								echo '{message: "Welcome!", status: "succeeded"}';
								http_response_code(200);
							} else {
								echo '{message: "Unidentified User! Security Breached" status: "failed"}';
							}
						}
					}
				
				} catch (PDOException $e){
				echo '{message: "' . $e->getMessage() . '", status: "failed"}';
				}			
			}

			// LOGOUT
			elseif(ISSET($_GET['url']) && $_GET['url']=='logout'){
				
			$data = file_get_contents('php://input');
			$data = json_decode($data, true);
				
				$authToken = $data['authToken'];
				$hashedToken = sha1($authToken);
				
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM login_tokens WHERE token = :token";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':token', $hashedToken, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
				
					if (empty($row_count)){
						echo '{message: "User already logged out!", status: "failed"}';
					} else {
						echo '{message: "You have successfully logged out!", status: "succeeded"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{message: "' . $e->getMessage() . '", status: "failed"}';
				}			
			} else {
				echo '{message: "Invalid Request!", status: "failed"}';
				http_response_code(405);
			}		
		break;

		
		
		case 'PUT';                                                  		// PUT REQUESTS
			
			//Update Customer info
			if (ISSET($_GET['url']) && $_GET['url']=="update-account"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$customerName = $data['customerName'];
				$customerEmail = $data['customerEmail'];
				$customerContactNumber = $data['customerContactNumber'];
				
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "UPDATE Customers SET customerName=:customerName, 
							customerContactNumber=:customerContactNumber
							WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':customerName', $customerName, PDO::PARAM_STR);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->bindParam(':customerContactNumber', $customerContactNumber, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
				
					if (empty($row_count)){
						echo '{message: "Error updating account! Invalid Email address!", status: "failed"}';
					} else {
						echo '{message: "Your account has been updated!", status: "succeeded"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{message: "' . $e->getMessage() . '", status: "failed"}}';
				}
			} 
			
			//Update Customer Address
			elseif (ISSET($_GET['url']) && $_GET['url']=="update-address"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$customerEmail = $data['customerEmail'];
				$customerAddress = $data['customerAddress'];
				
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "UPDATE Customers SET customerAddress=:customerAddress
							WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':customerAddress', $customerAddress, PDO::PARAM_STR);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
				
					if (empty($row_count)){
						echo '{message: "Error updating address! Invalid Email address!", status: "failed"}';
					} else {
						echo '{message: "Your address has been updated!", status: "succeeded"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{message: "' . $e->getMessage() . '", status: "failed"}}';
				}
			} 
			
			// Update new Password
			elseif(ISSET($_GET['url']) && $_GET['url']=="update-password"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$customerEmail = $data['customerEmail'];
				$currentPassword = $data['currentPassword'];
				$newPassword = $data['newPassword'];
				
				try {
					//Get db class
					$pdo = new db();
					
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT customerEmail, customerPassword FROM Customers WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					
					if (!$query){ 
						echo '{message: "' . $e->getMessage() . '", status: "failed"}';
					} else {
						$resultArray = $query->fetchAll();
						
						// Verify whether email address exists in DB
						if(!empty($resultArray)){
						
							$hashedPassword = $resultArray[0]['customerPassword'];
							
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
				
									$sql = "UPDATE Customers SET customerPassword=:customerPassword 
										WHERE customerEmail = :customerEmail";
				
									$query = $pdo->prepare($sql);
									$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
									$query->bindParam(':customerPassword', $newHash, PDO::PARAM_STR);
									$query->execute();
									$urow_count = $query->rowCount();
				
									if (empty($row_count)){
										echo '{message: "Error updating password!", status: "failed"}';
									} else {
										echo '{message: "Password Updated!", status: "succeeded"}';
										http_response_code(200);
									}	
								} catch (PDOException $e){
									echo '{message: "' . $e->getMessage() . '", status: "failed"}';
								}
							} else {
								echo '{message: "Invalid Password!", status: "failed"}';
							}
						} else {
							echo '{message: "Email address not registered!", status: "failed"}';
						}							
					}
				
				} catch (PDOException $e){
					echo '{message: "' . $e->getMessage() . '", status: "failed"}';
				}	
			} else {
				echo '{message: "Invalid Request!", status: "failed"}';
				http_response_code(405);
			}	
		break;

		
		
		case 'DELETE'; 
                                               		 // DELETE REQUESTS
			
			
			// Delete User Account
			if(ISSET($_GET['url']) && $_GET['url']=='delete-account'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
			
				$customerEmail = $data['customerEmail'];
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM Customers WHERE customerEmail = :customerEmail";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					$row_count = $query->rowCount();
				
					if (empty($row_count)){
						echo '{message: "Error deleting user. The account does not exist!", status: "failed"}';
					} else {
						echo '{message: "User Deleted!", status: "succeeded"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{message: "' . $e->getMessage() . '", status: "failed"}';
				}	
			} else {
				echo '{message: "Invalid Request!", status: "failed"}';
				http_response_code(405);
			}	
		break;

		
		
		default;
			echo '{message: "Invalid Request!", status: "failed"}';
			http_response_code(405);
		break;
	}
	
?>

