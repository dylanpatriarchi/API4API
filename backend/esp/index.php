<?php
$method = $_SERVER['REQUEST_METHOD'];
include '../conn.php';

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
		$sql = "SELECT * FROM esp WHERE id_esp=".(int)$_REQUEST["id"];
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
	$request_body = file_get_contents('php://input');
	$data = json_decode($request_body);
	$id_esp = (int)$data->id_esp;
	$devKit = filter_var(htmlspecialchars($data->devKit));
	$number_pin = (int)$data->number_pin;

	$sql = "INSERT INTO esp (id_esp, devKit,number_pin) VALUES ('$id_esp', '$devKit',$number_pin)";
	if ($conn->query($sql)) {
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati inseriti con successo."));
	} else {
		var_dump(http_response_code(500));
	}
} elseif ($method === 'PUT') {
	$request_body = file_get_contents('php://input');
	$data = json_decode($request_body);
	$id_esp = (int)$data->id_esp;
	$devKit = filter_var(htmlspecialchars($data->devKit));
	$number_pin = (int)$data->number_pin;

	$sql = "UPDATE esp SET devKit=$devKit, number_pin='$number_pin' WHERE id_esp=$id_esp";
	if ($conn->query($sql)) {
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati aggiornati con successo."));
	} else {
		var_dump(http_response_code(500));
	}
} elseif ($method === 'DELETE') {
	$id_esp = (int)$_REQUEST["id"];

	$sql = "DELETE FROM esp WHERE id_esp=$id_esp";
	if ($conn->query($sql)) {
		header('Content-Type: application/json');
		echo json_encode(array("message" => "Dati cancellati con successo."));
	} else {
		var_dump(http_response_code(500));
	}
} else {
var_dump(http_response_code(501));
}
?>
