<?php
$method = $_SERVER['REQUEST_METHOD'];
include 'conn.php';

if ($method === 'GET') {
	if(!isset($_REQUEST["id"])){
		$sql = "SELECT * FROM esp";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"id_esp"=>$row["id_esp"],
					"devKit" => $row["devKit"],
					"number-pin"=>$row["number_pin"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}	
	}else{
		$sql = "SELECT * FROM esp WHERE id_esp=".$_REQUEST["id"];
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"id_esp"=>$row["id_esp"],
					"devKit" => $row["devKit"],
					"number-pin"=>$row["number_pin"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}	
	}
} elseif ($method === 'POST') {
	// Leggi il payload JSON inviato nella richiesta
	$request_body = file_get_contents('php://input');
	$data = json_decode($request_body);
	$id_esp = $data->id_esp;
	$devKit = $data->devKit;
	$number_pin = $data->number_pin;

	$sql = "INSERT INTO esp (id_esp, devKit,number_pin) VALUES ('$id_esp', '$devKit',$number_pin)";
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
	$id_esp = $data->id_esp;
	$devKit = $data->devKit;
	$number_pin = $data->number_pin;

	$sql = "UPDATE esp SET devKit=$devKit, number_pin='$number_pin' WHERE id_esp=$id_esp";
	if ($conn->query($sql) === TRUE) {
		// Restituisci una risposta di successo
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati aggiornati con successo."));
	} else {
		// Restituisci una risposta di errore
		var_dump(http_response_code(500));
	}
} elseif ($method === 'DELETE') {
	$id_esp = $_REQUEST["id"];

	$sql = "DELETE FROM esp WHERE id_esp=$id_esp";
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
