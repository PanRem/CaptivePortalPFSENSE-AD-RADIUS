<?php

//import de la fonction validated
require "captiveportal-appel.php";

//definition des variables globales
global $userName;
global $password;
global $distant;
$distant = "http://serveur-radius";
global $mailerror;
$mailerror = "";
global $firstcall;
$firstcall = true;

//fonction de "nettoyage" des entrées utilisateur
//utile pour protéger de toute injection
//gestion également des espaces
function cleanInput($input) {
	$search = array(
	'@<script[^>]*?>.*?</script>@si',   /* strip out javascript */
	'@<[\/\!]*?[^<>]*?>@si',            /* strip out HTML tags */
	'@<style[^>]*?>.*?</style>@siU',    /* strip style tags properly */
	'@<![\s\S]*?--[ \t\n\r]*>@',         /* strip multi-line comments */
	'/[\r\n]/'                           /* supprimer les caractères de nouvelle ligne */
	);

	//remplacement des accents et autres caractère gênant
	$output = preg_replace($search, '', $input);
	$output = str_replace(
        array('à', 'â', 'ä', 'á', 'ã', 'å', 'ç', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ñ', 'ó', 'ò', 'ô', 'ö', 'õ', 'ú', 'ù', 'û', 'ü', 'ý', 'ÿ', ' '),
        array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', ''),
        $output
    );
	$output = preg_replace('/[^a-z0-9.\-_@]/', '', $output);
	$output = escapeshellarg($output);
	return $output;
}

//fonction de mise en forme pour pfsense et d'envoi du formulaire final au portail de pfsense
function Login()
{//debut login
	global $userName;
	global $password;
?>
<!DOCTYPE html>
<html>
	<head>
		<style>
			@media screen and (min-width: 768px) {
				#submitbtn {
					padding: 15px 30px;
					font-size: 18px;
				}
			}
			@media screen and (max-width: 767px) {
				#submitbtn {
					padding: 10px 20px;
					font-size: 16px;
				}
			}
		</style>
	</head>
	<body>
	<h1>Javascript est désactivé, cliquez sur "Continuer".</h1>
	<!-- Do not modify anything in this form as pfSense needs it exactly that way -->
		<form name="loginForm" method="post" action="$PORTAL_ACTION$">
			<input name="auth_user" type="hidden" value="<?php echo $userName; ?>">
			<input name="auth_pass" type="hidden" value="<?php echo $password; ?>">
			<input name="zone" type="hidden" value="$PORTAL_ZONE$">
			<input name="redirurl" type="hidden" value="$PORTAL_REDIRURL$">
			<input id="submitbtn" name="accept" type="submit" value="Continuer">
		</form>
		<script type="text/javascript">
		//autovalidation via JS
			document.getElementById("submitbtn").click();
		</script>
	</body>
</html>
<?php
}//fin Login


//vérification si appel avec paramètre ou non et lesquels
if (isset($_POST["userName"]) && isset($_POST["password"]))
{
	//firstcall = false permet de bloquer l'affichage des formulaire de base
	$firstcall = false;
	//cas où on a un utilisateur interne
	//mise en forme de username/password et appel a login pour envoyer a pfsense
	$userName = strtolower(cleanInput(htmlspecialchars($_POST["userName"])));
	$password = cleanInput(htmlspecialchars($_POST["password"]));
	Login();
} 
elseif (isset($_POST["nom"]) && isset($_POST["prenom"]) && isset($_POST["mail"]))
{
	//dans le cas d'un extérieur
	//on met en forme les entrées
	$nom = strtolower(cleanInput(htmlspecialchars($_POST["nom"])));
	$prenom = strtolower(cleanInput(htmlspecialchars($_POST["prenom"])));
	$mail = strtolower(cleanInput(htmlspecialchars($_POST["mail"])));
	list($user, $domainMail) = explode("@", $mail);

	//on vérifie au mieux le mail
	//sa forme et l'existence d'un serveur mail associé au domaine
	if (filter_var($mail, FILTER_VALIDATE_EMAIL) && checkdnsrr($domainMail, "MX")) {
		//firstcall = false permet de bloquer l'affichage des formulaire de base
		$firstcall = false;
		//si c'est bon on fait créer le compte avec la fonction validated
		$back = validated($nom,$prenom,$mail,$distant);
		//on met alors en forme les retours de validated
		$userName = strtolower(cleanInput($back["user"]) . "@domain.local");
		$password = cleanInput($back["pass"]);
		//et on appelle Login pour envoyer au pfsense
		Login();
	} else {
		$mailerror = "Veuillez entrer un mail valide.";
	}
}

//si aucun blocage n'a eu lieu alors on est dans le cas soit d'une erreur de mail
//soit du premier appel (sans paramètre)
//on affiche alors le formulaire "standard"
if($firstcall) 
{//debut page standard
?>
<!DOCTYPE html>
<html>
<head>
  <title>Portail Wifi</title>
  <?php 
  //récupération des ressources
  echo '<link rel="icon" type="image/png" sizes="192x192" href="'.$distant.'/logo.png">'; 
  echo '<link rel="stylesheet" href="'.$distant.'/bootstrap.css">';
  echo '<script src="'.$distant.'/jquery.js"></script>';
  echo '<script src="'.$distant.'/bootstrap.js"></script>';
  ?>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
		<?php echo 'background-image: url("'.$distant.'/image.png");'; ?>
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  background-attachment: fixed;
	}
	
    h5 {
		font-size: 180%;
		text-decoration-line: underline;
	}
	h4 {
		font-size: 140%;
		text-decoration-line: underline;
	}
	h3 {
		text-decoration-line: underline;
	}
	
	p, label {
		font-size: 125%;
	}
	
	span {
		font-size: 110%;
		font-style: italic;
	}
	
	#error {
		color: red;
		font-size: 125%;
		background-color: rgba(152, 251, 152, 0.8);
		text-align: center;
	}

    .logo {
      width: auto;
      height: 40vh;
      float: left;
      margin-right: 4%;
    }

    .transparent-bg {
      background-color: rgba(152, 251, 152, 0.8);
    }
	
	.modal-dialog {
	  background-color: rgb(152, 251, 152);
	}
	
	.modal-content, .modal-header, .modal-body, .card {
		background-color: rgba(0,0,0,0);
		border: none;
	}
	
	.btn {
		width: 30%;
		height: 30%;
		background-color: rgb(0, 100, 0);
		border: 2px solid rgb(0, 100, 0);
		filter: brightness(85%);
		white-space: nowrap;
		text-align: center;
	}
	
	.btn:hover {
		box-shadow: 0 12px 16px 0 rgba(0,0,0,0.24), 0 17px 50px 0 rgba(0,0,0,0.19);
		background-color: rgb(0, 100, 0);
		border: 2px solid black;
		filter: brightness(100%);
		white-space: nowrap;
		text-align: center;
	}
	
	.form-control {
		background-color: rgb(143, 188, 143);
		color: black;
	}
	
	.form-check-input:hover, .form-control:focus {
		border-color: rgb(104, 145, 162);
		background-color: rgb(143, 188, 143);
		color: black;
		outline: 0;
		-webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgb(104, 145, 162);
		box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgb(104, 145, 162);
}

    .card-text {
      clear: both;
    }


  </style>
</head>
<body>

  <div class="container">
    <div class="row">
      <div class="col-lg-6 transparent-bg">
        <div class="card">
          <div class="card-body">
			<?php echo '<img src="'.$distant.'/logo2.png" alt="Logo" class="logo">' ?>
            <!--contexte-->
            <p class="card-text">Bienvenue sur le wifi. <br><br>
								Les personnels et élèves doivent utiliser le formulaire utilisateur et leurs identifiants réseau. <br><br>
								Les utilisateurs extérieur doivent utiliser le formulaire Extérieur. <br>
								Aucun mail de démarchage ne sera envoyé au mail renseigné.<br><br>
								L'accès à ce réseau ne permet qu'un accès internet et aucun accès aux ressources internes de l'établissement</p>
          </div>
        </div>
      </div>
      <div class="col-lg-6 transparent-bg">
        <div class="card">
          <div class="card-body">
			<!--bouton de changement de formulaire-->
            <div id="formSwitch" style="display: none;">
              <button class="btn btn-primary" onclick="switchForm()">Extérieur</button>
            </div>
            <hr>
			<!--debut formulaire utilisateur interne-->
            <div id="formContent">
              <h4 id="formTitle">Formulaire Utilisateur</h4>
              <form method="POST" id="baseForm">
                <div class="form-group">
                  <label for="ID">Identifiant</label>
                  <input type="text" class="form-control" id="ID" name="userName" required>
                </div>
                <div class="form-group">
                  <label for="pwd">Mot de Passe</label>
                  <input type="password" class="form-control" id="pwd" name="password" required>
                </div>
				<div class="form-group">
					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="acceptTerms" name="acceptTerms" required>
						<label class="form-check-label" for="acceptTerms" id="terms">
						J'accepte les <a href="captiveportal-annex.html">conditions d'utilisation</a>
						</label>
					</div>
				</div>
                <button type="submit" class="btn btn-primary" name="accept">Valider</button>
              </form>
            </div>
            <!--fin formulaire utilisateur interne-->
			<!--debut formulaire extérieur-->
			<div id="guestFormContent">
              <h4 id="guestFormTitle">Formulaire Extérieur</h4>
              <form method="POST" id="guestForm">
                <div class="form-group">
                  <label for="nom">Nom</label>
                  <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                <div class="form-group">
                  <label for="prenom">Prénom</label>
                  <input type="text" class="form-control" id="prenom" name="prenom" required>
                </div>
                <div class="form-group">
                  <label for="mail">Adresse Mail</label>
                  <input type="email" class="form-control" id="mail" name="mail" required>
                </div>
				<div class="form-group">
					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="guestAcceptTerms" name="acceptTerms" required>
						<label class="form-check-label" for="guestAcceptTerms" id="guestTerms">
						J'accepte les <a href="captiveportal-annex.html">conditions d'utilisation</a>
						</label>
					</div>
				</div>
                <button type="submit" class="btn btn-primary">Valider</button>
              </form>
            </div>
			<!--fin formulaire extérieur-->
		  </div>
        </div>
      </div>
    </div>
  </div>

<!--affichage des conditions d'utilisations-->
<div id="termsModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title">Conditions d'utilisation</h1>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php
		//récupération du contenu de la page annex contenant les conditions d'utilisations
          $htmlContent = file_get_contents('captiveportal-annex.html');
          echo $htmlContent;
        ?>
      </div>
    </div>
  </div>
</div>


<script>
//le javascript simplifie la navigation mais tout est fonctionnel sans

//on cache le formulaire extérieur
var guestFormContent = document.getElementById('guestFormContent');
var formSwitch = document.getElementById('formSwitch');
guestFormContent.style.display = 'none';
formSwitch.style.display = 'block';

//on cache de base les conditions d'utilisation et on met en place les lien clicable qui les affiche
var guestTerms = document.getElementById('guestTerms');
var terms = document.getElementById('terms');
guestTerms.innerHTML = 'J\'accepte les <a href="javascript:void(0)" onclick="showTermsModal()">conditions d\'utilisation</a>';
terms.innerHTML = 'J\'accepte les <a href="javascript:void(0)" onclick="showTermsModal()">conditions d\'utilisation</a>';

//fonction d'affichage des conditions d'utilisations
  function showTermsModal() {
    $('#termsModal').modal('show');
  }
  
  //fonction de switch d'un formulaire a l'autre
  function switchForm() {
    var formContent = document.getElementById('formContent');
    var guestFormContent = document.getElementById('guestFormContent');
    var formSwitch = document.getElementById('formSwitch');

    if (formContent.style.display === 'none') {
      formContent.style.display = 'block';
      guestFormContent.style.display = 'none';
      formSwitch.innerHTML = '<button class="btn btn-primary" onclick="switchForm()">Extérieur</button>';
    } else {
      formContent.style.display = 'none';
      guestFormContent.style.display = 'block';
      formSwitch.innerHTML = '<button class="btn btn-primary" onclick="switchForm()">Utilisateur</button>';
    }
  }
</script>
</body>
</html>

<?php
}//fin page standard
?>