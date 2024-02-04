# Captive Portal Pfsense/AD/RADIUS

Dispositif permettant de créer une page personnalisée pour un portail captif sur pfsense. Le but est d'authentifier les utilisateurs d'un AD Via un RADIUS mais également de permettre à des utilisateurs extérieurs de pouvoir se connecter avec leur nom/prenom/mail.

L'amélioration faisable serait peut être de mettre en place une vérification d'identité de l'utilisateur externe via un sms ou un mail. 

Le cas d’utilisation interne étant standard je vais détailler uniquement le cas d’utilisation externe avec les solutions misent en place.

# SEQUENCE CONNEXION EXTERIEUR

![diagramme de sequence dans le cas d'une connexion d'un utilisateur extérieur](https://github.com/PanRem/CaptivePortalPFSENSE-AD-RADIUS/assets/154988183/c413bb52-32e3-4da9-80c8-c4dc32ece4cf)

# FONCTIONNEMENT

(je précise que tous les noms de compte/serveur/OU ont été "anonymisé" et sont donc à adapter)

## 1.	Documents

Pour fonctionner le portail a besoin de documents et de ressources. Voici leur répartition :
-	Portail.php -> page php du portail, situé sur le pfsense
-	Error.php -> page d’erreur du portail, situé sur le pfsense
-	Captiveportal-annex.html -> page contenant les conditions d’utilisation du wifi, situé sur le pfsense dans les fichiers complémentaires
-	Captiveportal-appel.php -> page appelant le recieve.php, situé sur le pfsense dans les fichiers complémentaires.
-	Recieve.php -> page appelé par le portail et appelant le script powershell, situé sur le RADIUS (NPS) a l’aide d’un service IIS paramétré sur un port spécial.
-	addCompte.ps1 -> script powershell appelé par Recieve.php, situé sur le RADIUS et communiquant avec l’AD
-	ressources (bootstrap.css/.js, image.jpg, jquerry.js, logo.png) -> ressources graphiques appelé par le portail, situé sur l’IIS du RADIUS sur le port classique (80)
-	Clean_Wifi.ps1 -> script powershell lancé toutes les 4h pour nettoyer les comptes AD temporaires, situé sur le serveur AD directement et lancé par une tâche planifié 


## 2.	Page Portail.php

La page Portail.php a globalement séparé en 2 fonctionnements :
-	Si elle a été appelée sans paramètre POST (premier appel)
-	Si elle a été appelée avec paramètres POST (appels utilisateur interne ou externe)

### a)	Appel sans paramètre

Dans le cas d’un appel sans paramètre, cela signifie que c’est le premier affichage de la page, la page affiche donc un petit texte, les 2 formulaires de log et les conditions d’utilisation.
Si l’utilisateur rempli avec un identifiant et mot de passe et valide, on passe au « cas interne ».
Si l’utilisateur rempli avec son nom/prenom/mail, on passe au « cas externe ».

### b)	Appel avec paramètres (cas externe)

Si les conditions sont remplies pour le cas externe cela signifie que l’utilisateur a rempli son nom/prenom/mail. On se sert donc de la fonction validated contenu dans la page « Captiveportal-appel.php » pour appeler la page « recieve.php » sur le serveur RADIUS (appelé « distant » dans le code) qui va à son tour appeler le script powershell pour créer le compte. Il reformule alors l’identifiant et le mot de passe du compte temporaire et l’envoi à la fonction Login qu’ils soient envoyés selon les standards de PFSENSE.

### c)	Appel avec paramètres (cas interne)

Si les conditions sont remplies pour le cas interne alors on a un identifiant et un mot de passe, la page les met en forme comme il faut et envoie à la fonction Login qu’ils soient envoyés selon les standards de PFSENSE.

Dans le cas où Javascript est désactivé, le portail est toujours fonctionnel, il sera demandé juste un clic supplémentaire pour valider l’envoi.


## 3.	Captiveportal-appel.php

Cette page est une ressource pour le portail, elle contient une fonction importée dans portail.php.
La fonction en question est Validated.
Elle récupère les infos misent par l’utilisateur et l’ip du serveur distant, elle contient également une CLEF API qui doit être la même ici et dans la page « recieve.php » du RADIUS afin d’authentifier l’origine de la demande. 
La fonction forme une requête POST avec les infos dedans et la clef api puis l’envoi au serveur distant sur le bon port (déterminé arbitrairement lors de la config de IIS) à l’aide d’une requête CURL. Cela permet que l’utilisateur n’ai accès n’a rien. 
Le retour du CURL est le suffixe à mettre à la fin de l’utilisateur et du mot de passe en cas d’homonymes.
On forme alors le mot de passe et l’identifiant suivant la méthode défini (doit être la même dans appel et addCompte.ps1) et on le retourne à l’appel de fonction.

## 4.	Recieve.php

Récupère les données envoyé par POST et si la clef API est bonne, appel le script de création de compte avec les infos(addCompte.ps1).
Envoie ensuite en retour le suffixe si jamais c’est un homonyme.



Une fois l’aller-retour de création de compte effectué, la procédure d’authentification via pfsense/radius est standard (voir portail).
