# DeutschLernen

Petit projet PHP pour apprendre l'allemand — interface, scénarios et assistant IA (DeutschBot).

## Installation locale (XAMPP)

1. Placez le dossier dans le répertoire `htdocs` de XAMPP (déjà en place).
2. Importez la base de données depuis `sql/schema_v3.sql` si nécessaire.
3. Vérifiez `config.php` et définissez la constante API si utilisé :

```php
// Méthode rapide (pas recommandée en production)
define('OPENROUTER_API_KEY', 'votre_cle_openrouter_ici');
```

Ou ajoutez la clé dans la table `settings` (clé `openrouter_api_key`).

## Utilisation

Ouvrez `http://localhost/deutschlernen` dans votre navigateur (avec XAMPP démarré).

## Sécurité
- Ne pas committer les secrets (ex: `config.php` local). Ce fichier est ajouté à `.gitignore`.

## Contribuer
1. Fork / clone
2. Créez une branche, faites des changements
3. Ouvrez une Pull Request

---
Licence: MIT
