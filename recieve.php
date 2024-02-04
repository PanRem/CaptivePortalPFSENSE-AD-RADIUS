<?php

//fonction de formatage pour enlever tout caractère gênant.
function format($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = str_replace(
        array('à', 'â', 'ä', 'á', 'ã', 'å', 'ç', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ñ', 'ó', 'ò', 'ô', 'ö', 'õ', 'ú', 'ù', 'û', 'ü', 'ý', 'ÿ'),
        array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'),
        $string
    );
	$string = preg_replace('/[^a-z0-9.\-_@]/', '', $string);
	$string = escapeshellarg($string);
    return $string;
}
//définition de la clef api qui devra obligatoirement être envoyé par l'appelant
$staticapi = "Clef_API";

// Vérifier si les paramètres requis sont présents
if (isset($_POST['nom']) && isset($_POST['prenom']) && isset($_POST['mail']) && isset($_POST['api_key'])) {
    // Récupérer les valeurs des paramètres
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $Mail = $_POST['mail'];
    $apiKey = $_POST['api_key'];

    // Vérifier si la clé d'API est valide
    if ($apiKey === $staticapi) {
        // Supprimer les accents des valeurs des paramètres
        $nom = format($nom);
        $prenom = format($prenom);
        $Mail = format($Mail);
        // Appeler le script PowerShell avec les paramètres
        $commandePowerShell = "powershell.exe -ExecutionPolicy Bypass -File Chemin\\vers\\script\\addCompte.ps1 $nom $prenom $Mail";
		//on récupère le retour qui est le potentiel suffixe
        $resultat = shell_exec($commandePowerShell);        *
		//on le transmet alors a l'appelant
		echo $resultat;
    }
}

?>
