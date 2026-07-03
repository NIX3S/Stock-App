# Gestion de Stock Associative

Application web de gestion de stock destinée aux associations.

L'application est fournie avec un environnement **Docker** (PHP 8.4, Apache et MariaDB) permettant une installation simple et identique sous Windows, Linux et macOS.

---

# Prérequis

Avant de commencer, installez :

* Docker Desktop (Windows/macOS) ou Docker Engine + Docker Compose (Linux)
* OpenSSL
* Git (facultatif si vous téléchargez le projet au format ZIP)

Vérifiez que Docker est correctement installé :

```bash
docker --version
docker compose version
```

---

# Installation

## 1. Télécharger le projet

Cloner le dépôt :

```bash
git clone <URL_DU_DEPOT>
```

ou télécharger l'archive ZIP puis l'extraire.

---

## 2. Se placer dans le dossier du projet

```bash
cd <nom-du-projet>
```

Vous devez obtenir une arborescence similaire :

```
Dockerfile
docker-compose.yml

docker/
stock-app/
```

---

# Génération du certificat SSL

L'application fonctionne en HTTPS grâce à un certificat auto-signé.

Depuis la racine du projet :

```bash
mkdir -p docker/ssl

openssl req -x509 -nodes -days 365 \
-newkey rsa:2048 \
-keyout docker/ssl/apache-selfsigned.key \
-out docker/ssl/apache-selfsigned.crt
```

Deux fichiers sont créés :

```
docker/ssl/apache-selfsigned.crt
docker/ssl/apache-selfsigned.key
```

Ils seront automatiquement utilisés par Apache.

> Lors de la première connexion, votre navigateur affichera un avertissement de sécurité car il s'agit d'un certificat auto-signé. Il suffit d'accepter l'exception.

---

# Configuration de l'application

Éditer le fichier :

```
stock-app/.env
```

Configuration par défaut :

```env
APP_NAME="Gestion de Stock"
APP_ENV=production
APP_URL=http://localhost
APP_DEBUG=false

DB_HOST=db
DB_PORT=3306
DB_NAME=stock_app
DB_USER=stockuser
DB_PASS=5345

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=adresse@gmail.com
MAIL_PASS=mot_de_passe_application
MAIL_FROM=adresse@gmail.com
MAIL_FROM_NAME="Gestion de Stock"

SESSION_LIFETIME=120
INVITATION_DEFAULT_EXPIRY_DAYS=7
PASSWORD_RESET_EXPIRY_MINUTES=60
```

---

# Modifier APP_URL

L'application doit connaître son adresse réseau.

Par défaut :

```env
APP_URL=http://localhost
```

Si elle est accessible depuis un autre ordinateur du réseau, remplacez cette valeur par l'adresse IP de la machine.

Exemple :

```env
APP_URL=https://192.168.1.20
```

## Trouver son adresse IP

### Windows

```powershell
ipconfig
```

Repérer :

```
Adresse IPv4
```

### Linux

```bash
hostname -I
```

ou

```bash
ip addr
```

### macOS

```bash
ifconfig
```

---

# Configuration de Gmail

L'envoi d'e-mails (invitation, réinitialisation de mot de passe, etc.) utilise Gmail.

Google impose l'utilisation d'un **mot de passe d'application**.

Créer un mot de passe ici :

https://myaccount.google.com/apppasswords

Puis modifier :

```env
MAIL_USER=votre.adresse@gmail.com
MAIL_PASS=mot_de_passe_application
MAIL_FROM=votre.adresse@gmail.com
```

Le mot de passe classique de votre compte Google ne fonctionnera pas.

---

# Configuration MySQL

La configuration par défaut est :

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=stock_app
DB_USER=stockuser
DB_PASS=5345
```

Ces valeurs peuvent être adaptées selon votre environnement.

---

# Démarrer l'application

Depuis la racine du projet :

```bash
docker compose up --build
```

Le premier lancement :

* construit l'image PHP ;
* démarre Apache ;
* démarre MariaDB ;
* exécute automatiquement les scripts SQL présents dans le dossier `database`.

Pour arrêter l'application :

```bash
docker compose down
```

---

# Ouvrir les ports réseau

Pour accéder à l'application depuis un autre ordinateur du réseau local, les ports **80** et **443** doivent être accessibles.

---

# Windows (Docker Desktop classique)

Vous pouvez ouvrir les ports directement depuis le Pare-feu Windows (interface graphique) ou via PowerShell lancé en administrateur.

## Ouvrir les ports

```powershell
New-NetFirewallRule -DisplayName "Sortant TCP 80" -Direction Outbound -Action Allow -Protocol TCP -RemotePort 80 -Profile Any
New-NetFirewallRule -DisplayName "Sortant UDP 80" -Direction Outbound -Action Allow -Protocol UDP -RemotePort 80 -Profile Any
New-NetFirewallRule -DisplayName "Entrant TCP 80" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80 -Profile Any
New-NetFirewallRule -DisplayName "Entrant UDP 80" -Direction Inbound -Action Allow -Protocol UDP -LocalPort 80 -Profile Any

New-NetFirewallRule -DisplayName "Sortant TCP 443" -Direction Outbound -Action Allow -Protocol TCP -RemotePort 443 -Profile Any
New-NetFirewallRule -DisplayName "Sortant UDP 443" -Direction Outbound -Action Allow -Protocol UDP -RemotePort 443 -Profile Any
New-NetFirewallRule -DisplayName "Entrant TCP 443" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 443 -Profile Any
New-NetFirewallRule -DisplayName "Entrant UDP 443" -Direction Inbound -Action Allow -Protocol UDP -LocalPort 443 -Profile Any
```

## Fermer les ports

```powershell
Remove-NetFirewallRule -DisplayName "Sortant TCP 80"
Remove-NetFirewallRule -DisplayName "Sortant UDP 80"
Remove-NetFirewallRule -DisplayName "Entrant TCP 80"
Remove-NetFirewallRule -DisplayName "Entrant UDP 80"

Remove-NetFirewallRule -DisplayName "Sortant TCP 443"
Remove-NetFirewallRule -DisplayName "Sortant UDP 443"
Remove-NetFirewallRule -DisplayName "Entrant TCP 443"
Remove-NetFirewallRule -DisplayName "Entrant UDP 443"
```

---

# Windows + WSL

Lorsque Docker fonctionne sous WSL, il est également nécessaire de rediriger les ports Windows vers l'adresse IP actuelle de WSL.

Lancer PowerShell en administrateur.

## Activer la redirection du port 80

```powershell
# Adresse IP actuelle de WSL
$WSL_IP = wsl hostname -I
$WSL_IP = ($WSL_IP -split '\s+')[0].Trim()

Write-Host "IP WSL : $WSL_IP"

netsh interface portproxy delete v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=80 | Out-Null

netsh interface portproxy add v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=80 `
    connectaddress=$WSL_IP `
    connectport=80

netsh advfirewall firewall add rule `
    name="WSL Apache Port 80" `
    dir=in `
    action=allow `
    protocol=TCP `
    localport=80 | Out-Null

Write-Host ""
Write-Host "Port 80 redirigé vers $WSL_IP"
```

## Activer la redirection du port 443

```powershell
# Adresse IP actuelle de WSL
$WSL_IP = wsl hostname -I
$WSL_IP = ($WSL_IP -split '\s+')[0].Trim()

Write-Host "IP WSL : $WSL_IP"

netsh interface portproxy delete v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=443 | Out-Null

netsh interface portproxy add v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=443 `
    connectaddress=$WSL_IP `
    connectport=443

netsh advfirewall firewall add rule `
    name="WSL Apache Port 443" `
    dir=in `
    action=allow `
    protocol=TCP `
    localport=443 | Out-Null

Write-Host ""
Write-Host "Port 443 redirigé vers $WSL_IP"
```

## Désactiver les redirections

Depuis PowerShell (administrateur) :

```powershell
netsh interface portproxy delete v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=80

netsh advfirewall firewall delete rule `
    name="WSL Apache Port 80"

Write-Host "Transfert supprimé."

netsh interface portproxy delete v4tov4 `
    listenaddress=0.0.0.0 `
    listenport=443

netsh advfirewall firewall delete rule `
    name="WSL Apache Port 443"

Write-Host "Transfert supprimé."
```

---

# Accéder à l'application

Une fois les conteneurs démarrés :

```
https://localhost
```

ou

```
https://ADRESSE_IP_DE_LA_MACHINE
```

Exemple :

```
https://192.168.1.20
```

---
# 1ere connexion

Utilisateur :

```
admin@example.org
```

Mot de passe

```
ChangeMe123!
```

Il vous sera demandé de changer le mot de passe a la 1ere connexion puis vous pourrez changer le mail dans les Utilisateur > Gerer 
---
# Structure des conteneurs

Deux conteneurs sont utilisés :

| Conteneur | Description      |
| --------- | ---------------- |
| stock-web | PHP 8.4 + Apache |
| stock-db  | MariaDB 11       |

Les données de MariaDB sont conservées dans un volume Docker nommé :

```
db_data
```

---

# Mise à jour

Récupérer les modifications :

```bash
git pull
```

Puis reconstruire les conteneurs :

```bash
docker compose down
docker compose up --build
```

---

# Dépannage

## Vérifier les conteneurs

```bash
docker ps
```

## Voir les logs Apache/PHP

```bash
docker compose logs web
```

## Voir les logs MariaDB

```bash
docker compose logs db
```

## Réinitialiser complètement

⚠️ Cette commande supprime également la base de données.

```bash
docker compose down -v
docker compose up --build
```

---

# Technologies utilisées

* PHP 8.4
* Apache
* MariaDB 11
* Docker
* Docker Compose
* Bootstrap
* JavaScript ES6

---

# Documentation complémentaire

Pour obtenir davantage d'informations sur le projet, les choix techniques et son architecture, vous pouvez consulter les documents suivants :

- **`architecture-gestion-stock.md`** *(à la racine du dépôt)* : décrit l'architecture complète de l'application, les choix de conception, l'organisation des dossiers, le modèle de données, les flux métier, le système de permissions, les classes, les services, les contrôleurs et les principes de développement.

- **`stock-app/README.md`** : documentation interne de l'application contenant des informations plus détaillées sur son fonctionnement, son développement, son architecture logicielle ainsi que les différents modules.

Ces documents sont destinés aux développeurs ou aux administrateurs souhaitant comprendre le fonctionnement interne de l'application ou participer à son évolution.
---

# Licence

Projet développé pour la gestion de stock associative.
