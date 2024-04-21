<?php
$method = $_SERVER['REQUEST_METHOD'];
include '../conn.php';

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
		$sql = "SELECT * FROM parametri WHERE id='".(int)$_REQUEST["id"]."'";
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
    $id = (int)$data->id;
    $value = filter_var(htmlspecialchars($data->value));
    $sql = "UPDATE parametri SET value=".$value." WHERE id='".$id."'";
    if ($conn->query($sql)) {
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Dati inseriti con successo."));
    } else {
        http_response_code(500);
        echo json_encode(array("error" => "Errore interno del server."));
    }
} else {
var_dump(http_response_code(501));
}
?>
