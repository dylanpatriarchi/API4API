<?php
$method = $_SERVER['REQUEST_METHOD'];
include 'conn.php';

if ($method === 'GET') {
	if(!isset($_REQUEST["name"])){
		$sql = "SELECT * FROM beehives";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"name"=>$row["name"],
					"location" =>array(
						"lat"=>$row["lat"],
						"lon"=>$row["lon"]
					),
					"esp_type"=>array(
						"name_esp"=>$row["esp_type"],
						"href"=>"../esp/".$row["esp_type"]
					)
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}	
	}else{
		$sql = "SELECT * FROM beehives WHERE name='".$_REQUEST["name"]."'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"name"=>$row["name"],
					"location" =>array(
						"lat"=>$row["lat"],
						"lon"=>$row["lon"]
					),
					"esp_type"=>array(
						"name_esp"=>$row["esp_type"],
						"href"=>"../esp/".$row["esp_type"]
					)
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}	
	}
}elseif ($method === 'POST') {
	// Leggi il payload JSON inviato nella richiesta
	$request_body = file_get_contents('php://input');
	$data = json_decode($request_body);
	$name = $data->name;
	$lat = $data->lat;
	$lon = $data->lon;
	$esp_type = $data->esp_type;

	$sql = "INSERT INTO beehives (name, lat,lon,esp_type) VALUES ('$name', '$lat','$lon','$esp_type')";
	if ($conn->query($sql) === TRUE) {
		// Restituisci una risposta di successo
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati inseriti con successo."));
	} else {
		// Restituisci una risposta di errore
		var_dump(http_response_code(500));
	}
} elseif ($method === 'PUT') {
	// Leggi il payload JSON inviato nella richiesta
	$request_body = file_get_contents('php://input');
	$data = json_decode($request_body);

	// Esegui l'aggiornamento dei dati nel database
	$id_bhv = $data->id_bhv;
	$name = $data->name;
	$lat = $data->lat;
	$lon = $data->lon;
	$esp_type = $data->esp_type;

	$sql = "UPDATE beehives SET name=$devKit, lat='$lat', lon=$lon,esp_type=$esp_type WHERE id_bhv=$id_bhv";
	if ($conn->query($sql) === TRUE) {
		// Restituisci una risposta di successo
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati aggiornati con successo."));
	} else {
		// Restituisci una risposta di errore
		var_dump(http_response_code(500));
	}
} elseif ($method === 'DELETE') {
	$id_bhv = $_REQUEST["name"];

	$sql = "DELETE FROM beehives WHERE name=$id_bhv";
	if ($conn->query($sql) === TRUE) {
		// Restituisci una risposta di successo
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati cancellati con successo."));
	} else {
		// Restituisci una risposta di errore
		var_dump(http_response_code(500));
	}
} else {
// Metodo HTTP non riconosciuto
var_dump(http_response_code(501));
}
?>
