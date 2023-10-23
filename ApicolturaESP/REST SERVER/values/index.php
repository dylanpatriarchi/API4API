<?php
$method = $_SERVER['REQUEST_METHOD'];
include 'conn.php';
//echo '{"metodo":'.'"'.$method.'","json"=';
if ($method == 'GET') {
	//echo 'pippo';
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
		$sql = "SELECT * FROM (value JOIN beehives ON fk_id_bhv=id_bhv)JOIN esp ON esp_type=id_esp WHERE id_val=".$_REQUEST["id"];
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
    // Leggi il payload JSON inviato nella richiesta
    $request_body = file_get_contents('php://input');
	//echo 'ciao';
    $data = json_decode($request_body);
    $weight = $data->weight;
    $temperature = $data->temperature;
    $humidity = $data->humidity;
    $noise_level = $data->noise_level;
    $fk_id_bhv = $data->beehive;
    $timestamp = $data->timestamp;
	
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
    if ($conn->query($sql) === TRUE) {
        // Restituisci una risposta di successo
		http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Dati inseriti con successo."));
    } else {
        // Restituisci una risposta di errore
        http_response_code(500);
        echo json_encode(array("error" => "Errore interno del server."));
    }
}else {
// Metodo HTTP non riconosciuto
var_dump(http_response_code(501));
}

function mandaMail($nomeParametro){
	error_reporting(E_ALL);

	// Genera un boundary
	$mail_boundary = "=_NextPart_" . md5(uniqid(time()));

	$to = "XXXXXXXXX";
	$subject = "Alert valore superato";
	$sender = "postmaster@patriarchidylan.it";


	$headers = "From: $sender\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
	$headers .= "X-Mailer: PHP " . phpversion();
	
	// Corpi del messaggio nei due formati testo e HTML
	$text_msg = "messaggio in formato testo";
	$html_msg = "<b>messaggio</b> in formato <p><a href='http://www.aruba.it'>html</a><br><img src=\"http://hosting.aruba.it/image_top/top_01.gif\" border=\"0\"></p>";
	
	// Costruisci il corpo del messaggio da inviare
	$msg = "This is a multi-part message in MIME format.\n\n";
	$msg .= "--$mail_boundary\n";
	$msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$msg .= "Content-Transfer-Encoding: 8bit\n\n";
	$msg .= "Il parametro ".$nomeParametro." è stato superato, controllare lo stato dell'arnia";  // aggiungi il messaggio in formato text
	
	$msg .= "\n--$mail_boundary\n";
	$msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
	$msg .= "Content-Transfer-Encoding: 8bit\n\n";
	$msg .= "Il parametro ".$nomeParametro." è stato superato, controllare lo stato dell'arnia";  // aggiungi il messaggio in formato HTML
	
	// Boundary di terminazione multipart/alternative
	$msg .= "\n--$mail_boundary--\n";
	
	// Imposta il Return-Path (funziona solo su hosting Windows)
	ini_set("sendmail_from", $sender);
	
	// Invia il messaggio, il quinto parametro "-f$sender" imposta il Return-Path su hosting Linux
	if (mail($to, $subject, $msg, $headers, "-f$sender")) { 
	}
}
//echo "}";
?>
