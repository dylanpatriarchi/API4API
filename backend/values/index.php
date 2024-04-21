<?php
$method = $_SERVER['REQUEST_METHOD'];
include '../conn.php';
if ($method == 'GET') {
	if(!isset($_REQUEST["id"])){
		$sql = "SELECT * FROM (value JOIN beehives ON fk_id_bhv=id_bhv)JOIN esp ON esp_type=id_esp ORDER BY timestamp_val DESC";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"id"=>$row["id_val"],
					"weight" => $row["weight"],
					"temperature"=>$row["temperature"],
					"humidity"=>$row["humidity"],
					"noise_level"=>$row["noise_level"],
					"beehive"=>array(
						"name_beehive"=>$row["name"],
						"esp_code" => array(
							"esp_name"=>$row["id_esp"],
							"href"=>"../esp/".$row["id_esp"]
						)
					),
					"timestamp"=>$row["timestamp_val"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}
	}else{
		$sql = "SELECT * FROM (value JOIN beehives ON fk_id_bhv=id_bhv)JOIN esp ON esp_type=id_esp WHERE id_val=".(int)$_REQUEST["id"];
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$dato = array();
			while($row = $result->fetch_assoc()) {
				$dato[] = array(
					"id"=>$row["id_val"],
					"weight" => $row["weight"],
					"temperature"=>$row["temperature"],
					"humidity"=>$row["humidity"],
					"noise_level"=>$row["noise_level"],
					"beehive"=>array(
						"name_beehive"=>$row["name"],
						"esp_code" => array(
							"esp_name"=>$row["id_esp"],
							"href"=>"../esp/".$row["id_esp"]
						)
					),
					"timestamp"=>$row["timestamp_val"]
				);
			}
			header('Content-Type: application/json');
			echo json_encode(array("dato" => $dato));
		}
	}
}elseif ($method == 'POST') {
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $weight = (double)$data->weight;
    $temperature = (double)$data->temperature;
    $humidity = (double)$data->humidity;
    $noise_level = (double)$data->noise_level;
    $fk_id_bhv = filter_var(htmlspecialchars($data->beehive));
    $timestamp = filter_var(htmlspecialchars($data->timestamp));
	
	$sql2 = "SELECT * FROM parametri";
	$result2= $conn->query($sql2);
    if ($result2->num_rows > 0) {
		while($row =$result2->fetch_assoc()) {
			if($row["id"]=="weight"&&$weight>$row["value"]){
				mandaMail("peso");
			}else if($row["id"]=="temperature"&&$temperature>$row["value"]){
				mandaMail("temperatura");
			}else if($row["id"]=="humidity"&&$humidity>$row["value"]){
				mandaMail("umidità");
			}else if($row["id"]=="noise_level"&&$noise_level>$row["value"]){
				mandaMail("intensità rumore");
			}
		}
	}
    $sql = "INSERT INTO value (weight, temperature, humidity, noise_level, fk_id_bhv, timestamp_val) VALUES (".$weight.", ".$temperature.", ".$humidity.", ".$noise_level.", ".$fk_id_bhv.", '".$timestamp."')";
    if ($conn->query($sql)) {
		http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Dati inseriti con successo."));
    } else {
        http_response_code(500);
        echo json_encode(array("error" => "Errore interno del server."));
    }
}else {
var_dump(http_response_code(501));
}

function mandaMail($nomeParametro){
	error_reporting(E_ALL);
	$mail_boundary = "=_NextPart_" . md5(uniqid(time()));
	$to = "XXXXXXXXX";
	$subject = "Alert valore superato";
	$sender = "postmaster@patriarchidylan.it";
	$headers = "From: $sender\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
	$headers .= "X-Mailer: PHP " . phpversion();
	$text_msg = "messaggio in formato testo";
	$html_msg = "<b>messaggio</b> in formato <p><a href='http://www.aruba.it'>html</a><br><img src=\"http://hosting.aruba.it/image_top/top_01.gif\" border=\"0\"></p>";
	$msg = "This is a multi-part message in MIME format.\n\n";
	$msg .= "--$mail_boundary\n";
	$msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$msg .= "Content-Transfer-Encoding: 8bit\n\n";
	$msg .= "Il parametro ".$nomeParametro." è stato superato, controllare lo stato dell'arnia";
	$msg .= "\n--$mail_boundary\n";
	$msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
	$msg .= "Content-Transfer-Encoding: 8bit\n\n";
	$msg .= "Il parametro ".$nomeParametro." è stato superato, controllare lo stato dell'arnia";
	$msg .= "\n--$mail_boundary--\n";
	ini_set("sendmail_from", $sender);
	mail($to, $subject, $msg, $headers, "-f$sender");
}
?>
