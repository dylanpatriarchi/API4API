<?php
$method = $_SERVER['REQUEST_METHOD'];
include 'conn.php';

if ($method === 'GET') {
	if(!isset($_REQUEST["id"])){
		$sql = "SELECT * FROM parametri";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"types"=>$row["id"],
					"range" => $row["value"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}
	}else{
		$sql = "SELECT * FROM parametri WHERE id='".$_REQUEST["id"]."'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"types"=>$row["id"],
					"range" => $row["value"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}
	}
}elseif ($method === 'PUT') {
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $id = $data->id;
    $value = $data->value;
    $sql = "UPDATE parametri SET value=".$value." WHERE id='".$id."'";
    if ($conn->query($sql) === TRUE) {
        // Restituisci una risposta di successo
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Dati inseriti con successo."));
    } else {
        // Restituisci una risposta di errore
        http_response_code(500);
        echo json_encode(array("error" => "Errore interno del server."));
    }
} else {
// Metodo HTTP non riconosciuto
var_dump(http_response_code(501));
}
?>
