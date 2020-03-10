<?php 
	// Setting JSON to be HTTP Header format
	header('Content-Type: application/json');
	require "../../includes/db.php";
	require "../../includes/functions.php";
	
	$method = $_SERVER['REQUEST_METHOD'];
	
	switch ($method) {

		case 'GET';                                         		// GET REQUESTS
		
			// Get all Products by Vendor email
			if(ISSET($_GET['url']) && $_GET['url']=="search"){
			
				//INPUT
				$vendorEmail = $_GET['vendorEmail'];
		
				try {
					//Get DB class
					$pdo = new db();									//db class in db.php
	
					//Connect to db
					$pdo = $pdo->connect();								//connect fuction in db.php
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
		
					$sql = "SELECT * FROM Products 
							WHERE vendorEmail = :vendorEmail
							ORDER BY productPrice"; 
		
					$query = $pdo->prepare($sql);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->execute();
		
					if(!$query)
					{
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else{
			
						$resultArray = $query->fetchAll();
						$result = array('results'=>$resultArray);
						$result = json_encode($result, JSON_PRETTY_PRINT);
				
						echo $result;
					
						http_response_code(200);
					}
	
				} catch(PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} 
			
			// Get Product info by ID
			elseif(ISSET($_GET['url']) && $_GET['url']=="get-item") {
			
				$productID = $_GET['productID'];
		
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
				
					$sql = "SELECT * FROM Products WHERE productID = :productID";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':productID', $productID, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
					
					$resultArray = $query->fetchAll();
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

		
		
		case 'POST';                                                		// POST REQUESTS
			$data = file_get_contents('php://input');
			$data = json_decode($data, true);
			
			// Add new Product
			if(ISSET($_GET['url']) && $_GET['url']=="add"){					
		
				$productName = $data['productName'];
				$vendorEmail = $data['vendorEmail'];
				$productType = $data['productType'];
				$productDescription = $data['productDescription'];
				$productPrice = $data['productPrice'];
				if(ISSET($data['productImageURL'])){
					$productImageURL = $data['productImageURL'];
				} else{
					$productImage = "";
				}
				
				if(ISSET($data['productRating'])){
					$productRating = $data['productRating'];
				} else{
					$productRating = "";
				}

				try {
					//Get db class
					$pdo = new db();
			
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "INSERT INTO Products(productName, vendorEmail, productType, productDescription, productImageURL, productPrice, productRating)
							VALUES(:productName, :vendorEmail, :productType, :productDescription, :productImageURL, :productPrice, :productRating)";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':productName', $productName, PDO::PARAM_STR);
					$query->bindParam(':vendorEmail', $vendorEmail, PDO::PARAM_STR);
					$query->bindParam(':productType', $productType, PDO::PARAM_STR);
					$query->bindParam(':productDescription', $productDescription, PDO::PARAM_STR);
					$query->bindParam(':productPrice', $productPrice, PDO::PARAM_STR);
					$query->bindParam(':productImageURL', $productImageURL, PDO::PARAM_STR);
					$query->bindParam(':productRating', $productRating, PDO::PARAM_STR);

				
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Item Added!"}}';
						http_response_code(200);
					}
				
				} catch (PDOException $e){
					echo '{error: {text: ' . $e->getMessage() . '}}';
				}
			} 
			
			else {
				echo '{message: {text: "Invalid Request!"}}';
				http_response_code(405);
			}		
		break;

		
		
		case 'PUT';                                                  		// PUT REQUESTS
			
			//Upadte Product info
			if (ISSET($_GET['url']) && $_GET['url']=="update"){
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
		
				$productName = $data['productName'];
				$productID = $data['productID'];
				$productType = $data['productType'];
				$productDescription = $data['productDescription'];
				$productPrice = $data['productPrice'];
				if(ISSET($data['productImage'])){
					$productImage = $data['productImage'];
				} else{
					$productImage = "";
				}
				

				try {
					//Get db class
					$pdo = new db();
			
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "UPDATE Products SET productName=:productName, productType=:productType, 
							productDescription=:productDescription, productPrice=:productPrice, productImage=:productImage
							WHERE productID = :productID";
				
					$query = $pdo->prepare($sql);
				
					$query->bindParam(':productID', $productID, PDO::PARAM_STR);
					$query->bindParam(':productName', $productName, PDO::PARAM_STR);
					$query->bindParam(':productType', $productType, PDO::PARAM_STR);
					$query->bindParam(':productDescription', $productDescription, PDO::PARAM_STR);
					$query->bindParam(':productPrice', $productPrice, PDO::PARAM_STR);
					$query->bindParam(':productImage', $productImage, PDO::PARAM_STR);

				
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Item Updated!"}}';
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

		
		
		case 'DELETE'; 	                                           		 // DELETE REQUESTS
			
			// Delete Product
			if(ISSET($_GET['url']) && $_GET['url']=='delete'){
				
				$data = file_get_contents('php://input');
				$data = json_decode($data, true);
			
				$productID = $data['productID'];
			
				try {
					//Get db class
					$pdo = new db();
				
					//Connect to db
					$pdo = $pdo->connect();
				
					$sql = "DELETE FROM Products WHERE productID = :productID";
				
					$query = $pdo->prepare($sql);
					$query->bindParam(':productID', $productID, PDO::PARAM_STR);
					$query->execute();
				
					if (!$query){
						echo '{error: {text: ' . $e->getMessage() . '}}';
					} else {
						echo '{message: {text: "Item Deleted!"}}';
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

