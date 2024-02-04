   <?php
	
	//permet de construire une requete et de l'envoyer au serveur distant pour créer / vérifier le compte
	//renvoi en retour un dictionnaire avec l'username et le mot de passe
	function validated($nom,$prenom,$adresseMail,$dist){
		//ici on défini le port a appelé (défini arbitrairement coté serveur)
		$dist = $dist . ":8080/recieve.php";
		//et la clef api
        $apiKey = "Clef_API";

		//on construit la requete
		$request = array(
			'nom' => $nom,
			'prenom' => $prenom,
			'mail' => $adresseMail,
			'api_key' => $apiKey
			);
			
		$options = array(
		CURLOPT_URL => $dist,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query($request),
		CURLOPT_RETURNTRANSFER => true
		);

        // Effectuer l'appel au serveur distant
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		//récupération du potentiel suffixe
		$response = curl_exec($curl);
		curl_close($curl);

		// Construction des données à retourner
		$user = "WIFI." . str_replace('"', "", substr($nom,0,6)) . "." . str_replace('"', "", substr($prenom,0,6)) . $response;
		$pass = "P@ss." . str_replace('"', "", substr($nom,0,6)) . "." . str_replace('"', "", substr($prenom,0,6)) . $response;
	
		$retour = array(
			"user" => $user,
			"pass" => $pass
			);
		//enfin retour du dictionnaire
		return $retour;
	}
	
    ?>
