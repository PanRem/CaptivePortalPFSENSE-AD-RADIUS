<!DOCTYPE html>
<html>
<head>
  <title>Portail Wifi (Erreur)</title>
  <link rel="icon" href="http://serveur-radius/logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        
        h1 {
            color: #FF0000;
        }
        
        p {
            color: #666666;
        }
    </style>
</head>
<body>
    <h1>Erreur de connexion</h1>
    <p>Une erreur s'est produite lors de la connexion au réseau.</p>
    <p>Veuillez vérifier vos informations d'identification et réessayer.</p>
    <p>Si le problème persiste, veuillez contacter l'administrateur réseau.</p>
	<p><a href="https://www.google.com/">Retour a l'accueil</a></p>
	<br><br>
	<?php
	echo "<span>message de l'erreur: \$PORTAL_MESSAGE\$</span>"
	?>
</body>
</html>
