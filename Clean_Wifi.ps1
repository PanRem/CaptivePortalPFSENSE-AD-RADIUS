# Configuration du serveur NPS distant
$serverName = "serveur-radius"

# Chemin de l'unité d'organisation (OU) cible
$ouPath = "OU=WIFI,DC=domain,DC=local"

# Chemin du fichier annexe pour stocker les identifiants des comptes sans dernière connexion
$fileName = "Chemin\vers\buffer.txt"

#vérification de la présence du fichier, sinon création
$text = ".`n"
if (-not (Test-Path $fileName)) {
    $text | Out-File -FilePath $fileName -Encoding UTF8
    }

# Récupération de la liste des utilisateurs dans l'OU spécifiée
$users = Get-ADUser -Filter * -SearchBase $ouPath

# Boucle à travers tous les utilisateurs
foreach ($user in $users) {
    $username = $user.SamAccountName

    # Ignorer le compte "WIFI_SRV"
    if ($username -eq "WIFI_SRV") {
        continue
    }

	#on regarde dans les event du serveur radius si l'utilisateur a été connecté et on récupère la dernière date de connexion
    $lastLogon = Get-WinEvent -ComputerName $serverName -FilterHashtable @{
        LogName = "Security"
        ID = 6273
        Data = $username
        MaxEvents = 1
        Oldest
    } -ErrorAction SilentlyContinue | Select-Object -ExpandProperty TimeCreated

	#si il y a eu connexion
    if ($lastLogon) {
		#on récupère l'heure
        $lastLogonTime = $lastLogon | Select-Object -ExpandProperty DateTime
        $timeDifference = (Get-Date) - $lastLogonTime

		#si cela fait + de 4 heure
        if ($timeDifference.TotalHours -gt 4) {
			#on supprime sa presence dans le buffer (si il y était)
            (Get-Content $fileName) | Where-Object { $_ -ne $username } | Set-Content $fileName
			#et on supprime le compte
            Remove-ADUser -Identity $user -Confirm:$false
        }
    } else {
		#si aucune connexion du compte
		#on est obligé de faire un buffer pour éviter de supprimer un compte potentiellement en cours de connexion
        if ((Get-Content $fileName) -notcontains $username) {
			#si il n'est pas dans le buffer on l'y met, il sera alors potentiellement supprimé dans 4h
            Add-Content -Path $fileName -Value $username
        } else {
			#si il était déjà dans le buffer, on l'y enlève et on supprime le compte
            (Get-Content $fileName) | Where-Object { $_ -ne $username } | Set-Content $fileName
            Remove-ADUser -Identity $user -Confirm:$false
        }
    }
}