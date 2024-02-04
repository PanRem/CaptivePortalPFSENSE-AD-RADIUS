# déclaration des paramètres
param (
    [Parameter(Mandatory=$true)]
    [string]$nom,
    
    [Parameter(Mandatory=$true)]
    [string]$prenom,
    
    [Parameter(Mandatory=$true)]
    [string]$mail
)

# déclaration des variables
#OU de l'AD ou l'on créé les comptes
$ou = "OU=WIFI,DC=domain,DC=local"
#Composition du nom de compte
$UserName = "WIFI."+$nom.Substring(0,6)+"."+$prenom.Substring(0,6)
#composition du mot de passe
$MDP = "P@ss."+$nom.Substring(0,6)+"."+$prenom.Substring(0,6)

#déclaration des credential du compte permettant de créer les comptes temporaire.
# le dit compte doit avoir des droits dans l'OU ciblé et QUE dans l'OU ciblé pour des raisons de sécurité évidente
$User = "domain.local\WIFI_SRV"
$PWord = ConvertTo-SecureString -String "Password" -AsPlainText -Force
$Credential = New-Object -TypeName System.Management.Automation.PSCredential -ArgumentList $User, $PWord

#on envoie les commandes en remote sur le controleur de domaine
Invoke-Command -ComputerName srv-Domain -Credential $Credential -ScriptBlock {
	param($UserName, $MDP, $nom, $prenom, $mail, $ou)

	# On doit Vérifier si l'utilisateur existe déjà dans l'OU

	#déclaration des variables
	$suffix = $null
	$continue = $false
	
	#on vérifie si l'user existe sur 2 critères
	#si il existe avec un mail différent alors il y a un homonyme on rajoute alors un chiffre a la fin
	#si il existe avec le meme mail alors c'est juste une reconnextion pas de nouveau compte a créer
	#sinon, il existe pas et on créer le compte
	do{
		$temp = $UserName + $suffix
		$userExists = Get-ADUser -Filter {SamAccountName -eq $temp} -SearchBase $ou -Property EmailAddress -ErrorAction SilentlyContinue 

		if ($userExists) {
    
			if($userExists.EmailAddress -ne $mail){
                #cas ou user existe mais pas meme mail
				#ici on incrémente le chiffre de fin et on recommence la vérification
				$continue = $true
				$suffix++
			} else {
				# user existe + meme email -> reconnexion
				$continue = $false
			}
		} else {
			#user n'existe pas -> on créer
			$continue = $false
        }
	}while ($continue)
	
	#on prépare les logs
	$annee = Get-Date -Format "yyyy"
	$Logs = "Chemin\vers\logs\log_creation_$annee.txt"
	$dateHeure = Get-Date -Format "yyyy-MM-dd_HH:mm:ss"
	$message = $null

	#si on est dans le cas où on doit créer un compte
	if(!$userExists)
	{
		$UserName = $UserName + $suffix
		$UserName2 = $UserName+"@domain.local"
		$MDP=$MDP + $suffix
		$secu = ConvertTo-SecureString $MDP -AsPlainText -Force
		New-ADUser -Path $ou -UserPrincipalName $UserName2 -Name $UserName -GivenName $prenom -Surname $nom -EmailAddress $mail -AccountPassword $secu -PasswordNeverExpires 1 -CannotChangePassword 1 -Enabled 1
		$message = "Compte $UserName2 créé au nom de $nom $prenom mail: $mail"
	}
	else{
		#cas d'une reconnexion, on log l'entrée uniquement
		$message = "reconnexion de $UserName2 : $nom $prenom mail: $mail"
	}	
	
	#on écris dans les logs
	$nouvelleLigne = "$dateHeure   $message"
	Add-Content -Path $Logs -Value $nouvelleLigne

    #on renvoi alors le suffix pour que le programme appelant le connaisse. 
	Write-Host $suffix

} -ArgumentList $UserName, $MDP, $nom, $prenom, $mail, $ou
