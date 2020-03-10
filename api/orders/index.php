<?php 
	// Setting JSON to be HTTP Header format
	header('Content-Type: application/json');
	require "../../includes/db.php";
	require "../../includes/functions.php";
	
	$method = $_SERVER['REQUEST_METHOD'];
	
	switch ($method) {

		case 'GET';                                         		// GET REQUESTS
		
			// Get all Cart items by Customer email
			if(ISSET($_GET['url']) && $_GET['url']=="get-cart"){
			
				//INPUT
				$customerEmail = $_GET['customerEmail'];
		
				try {
					//Get DB class
					$pdo = new db();									//db class in db.php
	
					//Connect to db
					$pdo = $pdo->connect();								//connect fuction in db.php
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
					$sql = "SELECT v.vendorName, p.productName, p.productPrice, crt.QTY, crt.Add_Date from testdb.Cart crt 
							JOIN testdb.Customers cus on cus.customerEmail = crt.Customer_ID
							JOIN testdb.Products p on crt.Product_ID = p.ProductID
							JOIN testdb.Vendors v on p.vendorEmail = v.vendorEmail
							wHERE crt.Customer_ID = :customerEmail"; 
		
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
					
					if(!$query)
					{
						echo '{message: ' . $e->getMessage() . '}';
					} else{
						
						
						$resultArray = $query->fetchAll();
						
						// For Calculating Total Price
						$count = $query->rowCount();
						$Total_Price = floatval(0);
						
						for($i=0; $i<$count; $i++) {
							
							$item = $resultArray[$i];
							$Product_Price = $item->productPrice;
							$QTY = $item->QTY;
							
							$F_Price = floatval($Product_Price);
							$F_QTY = floatval($QTY);
								
							$Total_Price = $Total_Price + ($F_Price * $F_QTY);
							
						}
						
						
						$result = array('results'=>$resultArray, 'totalPrice'=>$Total_Price);
						$result = json_encode($result, JSON_PRETTY_PRINT);
				
						echo $result;
					
						http_response_code(200);
					}
					
		
				/*	if(!$query)
					{
						echo '{message: ' . $e->getMessage() . '}';
					} else{
			
						$items = $query->fetchAll();
						$count = $query->rowCount();
						
						$resultArray = array();
						$Total_Price = floatval('0');
						
						for ($i=0; $i<$count; $i++) {
							
							$item = $items[$i];
							$Product_ID = $item->Product_ID;
							$QTY = $item->QTY;
							$Date = $item->Add_Date;
							
							try {
							
								//Get DB class
								$pdo = new db();									//db class in db.php
	
								//Connect to db
								$pdo = $pdo->connect();								//connect fuction in db.php
								$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
								$sql = "SELECT * FROM Products
										WHERE productID = :productID"; 
		
								$query = $pdo->prepare($sql);
								$query->bindParam(':productID', $Product_ID, PDO::PARAM_STR);
								$query->execute();
								
								if(!$query)
								{
									echo '{message: ' . $e->getMessage() . '}';
								} else{
			
									$product = $query->fetch();
									
									$Product_Name = $product->productName;
									$Product_Price = $product->productPrice;
									$Vendor_Email = $product->vendorEmail;
									
									try {
							
										//Get DB class
										$pdo = new db();									//db class in db.php
	
										//Connect to db
										$pdo = $pdo->connect();								//connect fuction in db.php
										$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
										$sql = "SELECT * FROM Vendors
												WHERE vendorEmail = :vendorEmail"; 
		
										$query = $pdo->prepare($sql);
										$query->bindParam(':vendorEmail', $Vendor_Email, PDO::PARAM_STR);
										$query->execute();
								
										if(!$query)
										{
											echo '{message: ' . $e->getMessage() . '}';
										} else{
			
											$vendor = $query->fetch();
									
											$Vendor_Name = $vendor->vendorName;
									
											$objectArray = array(
																	"Product_ID" => $Product_ID,
																	"Product_Name" => $Product_Name,
																	"Product_Price" => $Product_Price,
																	"Product_QTY" => $QTY,
																	"Add_Date" => $Date,
																	"Vendor_Email" => $Vendor_Email,
																	"Vendor_Name" => $Vendor_Name,
											);
											
											
											array_push($resultArray, $objectArray);
											
											$F_Price = floatval($Product_Price);
											$F_QTY = floatval($QTY);
											
											$Total_Price = $Total_Price + ($F_Price * $F_QTY);
											
											
										}
									} catch(PDOException $e){
										echo '{message: ' . $e->getMessage() . '}';
									} 
									
								}
							} catch(PDOException $e){
								echo '{message: ' . $e->getMessage() . '}';
							} 
							
						}
						
						
						
						$result = array('results'=>$resultArray, 'Total_Price'=>$Total_Price);
						$result = json_encode($result, JSON_PRETTY_PRINT);
				
						echo $result;
						
						http_response_code(200);
					} */
	
				} catch(PDOException $e){
					echo '{message: ' . $e->getMessage() . '}';
				}
			} 
			
			// Get Orders by Customer email
			elseif(ISSET($_GET['url']) && $_GET['url']=="get-order") {
			
			
				$customerEmail = $_GET['customerEmail'];
		
				try {
					//Get DB class
					$pdo = new db();									//db class in db.php
	
					//Connect to db
					$pdo = $pdo->connect();								//connect fuction in db.php
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
					$sql = "SELECT * FROM Orders 
							WHERE Customer_ID = :customerEmail
							ORDER BY Trans_Date"; 
		
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerEmail', $customerEmail, PDO::PARAM_STR);
					$query->execute();
		
					if(!$query)
					{
						echo '{message: ' . $e->getMessage() . '}';
					} else{
						
						
						$resultArray = $query->fetchAll();
						$result = array('results'=>$resultArray);
						$result = json_encode($result, JSON_PRETTY_PRINT);
				
						echo $result;
					
						http_response_code(200);
					}
	
				} catch(PDOException $e){
					echo '{message: ' . $e->getMessage() . '}';
				}
			} else {
				echo '{message: "Invalid Request!"}';
				http_response_code(405);
			}	
			
		break;

		
		
		case 'POST';                                                		// POST REQUESTS
			$data = file_get_contents('php://input');
			$data = json_decode($data, true);
			
			// Add new Product
			if(ISSET($_GET['url']) && $_GET['url']=="add-cart"){					
		
				$ProductID = $data['productID'];
				$CustomerID = $data['customerEmail'];
				$Qty = $data['qty'];
				

				try {
					//Get db class
					$pdo = new db();
			
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "INSERT INTO Cart(Product_ID, Customer_ID, QTY)
							VALUES(:productID, :customerEmail, :qty)";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':productID', $ProductID, PDO::PARAM_STR);
					$query->bindParam(':customerEmail', $CustomerID, PDO::PARAM_STR);
					$query->bindParam(':qty', $Qty, PDO::PARAM_STR);

				
					$query->execute();
				
					if (!$query){
						echo '{message: ' . $e->getMessage() . '}';
					} else {
						echo '{message: "Item Added to Cart!"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{message: ' . $e->getMessage() . '}';
				}
			} 
			
			else {
				echo '{message: "Invalid Request!"}';
				http_response_code(405);
			}		
		break;

		
		
		case 'PUT';                                                  		// PUT REQUESTS
			
			//Upadte Cart Item
			if (ISSET($_GET['url']) && $_GET['url']=="update-cart"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$CartID = $data['cartID'];
				$ProductID = $data['productID'];
				$CustomerID = $data['customerEmail'];
				$Qty = $data['qty'];

				try {
					//Get db class
					$pdo = new db();
			
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "UPDATE Cart 
							SET Product_ID=:productID, Customer_ID=:customerEmail, QTY=:qty
							WHERE ID = :cartID";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':cartID', $CartID, PDO::PARAM_STR);
					$query->bindParam(':productID', $ProductID, PDO::PARAM_STR);
					$query->bindParam(':customerEmail', $CustomerID, PDO::PARAM_STR);
					$query->bindParam(':qty', $Qty, PDO::PARAM_STR);
				
					$query->execute();
				
					if (!$query){
						echo '{message: ' . $e->getMessage() . '}';
					} else {
						echo '{message: "Cart Updated!"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{message: ' . $e->getMessage() . '}';
				}
			} else {
				echo '{message: "Invalid Request!"}';
				http_response_code(405);
			}	
		break;

		
		
		case 'DELETE'; 	                                           		 // DELETE REQUESTS
			
			// Delete Item from Cart
			if(ISSET($_GET['url']) && $_GET['url']=='delete-item'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
			
				$CartID = $data['cartID'];
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM Cart WHERE ID = :cartID";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':cartID', $CartID, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{message: ' . $e->getMessage() . '}';
					} else {
						echo '{message: "Item deleted from Cart!"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{message: ' . $e->getMessage() . '}';
				};	
				
			} 
			elseif (ISSET($_GET['url']) && $_GET['url']=='delete-cart'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
			
				$CustomerID = $data['customerEmail'];
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE * FROM Cart WHERE CustomerID = :customerID";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':customerID', $CustomerID, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{message: ' . $e->getMessage() . '}';
					} else {
						echo '{message: "Cart is Empty!"}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
				echo '{message: ' . $e->getMessage() . '}';
				};	
				
			} else {
				echo '{message: "Invalid Request!"}';
				http_response_code(405);
			}	
		break;

		
		
		default;
			echo '{message: "Invalid Request!"}';
			http_response_code(405);
		break;
	}
	
?>

