<?php

function getCoordinates($address){ //Get coordinates from street addrress

	//Making an api request to google maps to get coordinates from the street address
 
	$address = str_replace(" ", "+", $address); // replace all the white space with "+" sign to match with google search pattern
 
	$url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";
 
	$response = file_get_contents($url);
 
	$json = json_decode($response,TRUE); //generate array object from the response from the web
 
	$latitude = $json['results'][0]['geometry']['location']['lat'];
	$longitude = $json['results'][0]['geometry']['location']['lng'];
 
	$location = [$latitude, $longitude];

	return $location;
 
}

function getAddress($lat, $lng) { //Get street address from coordinates

	//Making an api request to google maps to get street address from the coordinates
	
	$coordinates = $lat . "," . $lng;
	$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $coordinates . "&key=API_KEY"; 
	
	$response = file_get_contents($url);
 
	$json = json_decode($response,TRUE); //generate array object from the response from the web
	
	$address = $json['results'][0]['formatted_address'];
	
	return $address;
}

function getDistance($lat1, $lng1, $lat2, $lng2) {  //Get distance from two coordinates

	//Haversine Formula to Calculate Distance
	
    $radius = 3959;  //approximate mean radius of the earth in miles, can change to any unit of measurement, will get results back in that unit

    $delta_Rad_Lat = deg2rad($lat2 - $lat1);  //Latitude delta in radians
    $delta_Rad_Lng = deg2rad($lng2 - $lng1);  //Longitude delta in radians
    $rad_Lat1 = deg2rad($lat1);  //Latitude 1 in radians
    $rad_Lat2 = deg2rad($lat2);  //Latitude 2 in radians

    $sq_Half_Chord = sin($delta_Rad_Lat / 2) * sin($delta_Rad_Lat / 2) + cos($rad_Lat1) * cos($rad_Lat2) * sin($delta_Rad_Lng / 2) * sin($delta_Rad_Lng / 2);  //Square of half the chord length
    $ang_Dist_Rad = 2 * asin(sqrt($sq_Half_Chord));  //Angular distance in radians
    $distance = $radius * $ang_Dist_Rad;  

    return $distance;  
}  



?>