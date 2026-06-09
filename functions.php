<?php
// Multi-language translations
$translations = [
    'en' => [
        'app_name' => 'Community Forum',
        'login' => 'Login',
        'register' => 'Register',
        'logout' => 'Logout',
        'settings' => 'Settings',
        'dark_mode' => 'Dark Mode',
        'light_mode' => 'Light Mode',
        'language' => 'Language',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'username' => 'Username',
        'submit' => 'Submit',
        'discussion_rooms' => 'Discussion Rooms',
        'create_room' => 'Create New Room',
        'room_name' => 'Room Name',
        'room_description' => 'Description (optional)',
        'join_room' => 'Join Room',
        'send_message' => 'Send Message',
        'upload_file' => 'Upload File',
        'emoji' => 'Emoji',
        'type_message' => 'Type your message...',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure you want to delete this message?',
        'edit_prompt' => 'Edit your message:',
        'file_upload_error' => 'File upload failed',
        'login_error' => 'Invalid credentials',
        'register_error' => 'Registration failed',
        'register_success' => 'Registration successful! Please login.',
        'settings_saved' => 'Settings saved successfully!',
        'no_messages' => 'No messages yet. Be the first to send a message!',
        'online' => 'Online',
        'members' => 'Members',
        'dashboard' => 'Dashboard',
        'welcome' => 'Welcome',
    ],
    'fr' => [
        'app_name' => 'Forum Communautaire',
        'login' => 'Connexion',
        'register' => 'Inscription',
        'logout' => 'Déconnexion',
        'settings' => 'Paramètres',
        'dark_mode' => 'Mode Sombre',
        'light_mode' => 'Mode Clair',
        'language' => 'Langue',
        'email' => 'E-mail',
        'password' => 'Mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'username' => 'Nom d\'utilisateur',
        'submit' => 'Envoyer',
        'discussion_rooms' => 'Salons de Discussion',
        'create_room' => 'Créer un Salon',
        'room_name' => 'Nom du Salon',
        'room_description' => 'Description (optionnelle)',
        'join_room' => 'Rejoindre',
        'send_message' => 'Envoyer',
        'upload_file' => 'Télécharger',
        'emoji' => 'Émoji',
        'type_message' => 'Écrivez votre message...',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'delete_confirm' => 'Voulez-vous vraiment supprimer ce message ?',
        'edit_prompt' => 'Modifiez votre message :',
        'file_upload_error' => 'Échec du téléchargement',
        'login_error' => 'Identifiants invalides',
        'register_error' => 'Échec de l\'inscription',
        'register_success' => 'Inscription réussie ! Veuillez vous connecter.',
        'settings_saved' => 'Paramètres enregistrés !',
        'no_messages' => 'Aucun message. Soyez le premier à envoyer un message !',
        'online' => 'En ligne',
        'members' => 'Membres',
        'dashboard' => 'Tableau de bord',
        'welcome' => 'Bienvenue',
    ],
    'de' => [
        'app_name' => 'Community-Forum',
        'login' => 'Anmelden',
        'register' => 'Registrieren',
        'logout' => 'Abmelden',
        'settings' => 'Einstellungen',
        'dark_mode' => 'Dunkelmodus',
        'light_mode' => 'Hellmodus',
        'language' => 'Sprache',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'username' => 'Benutzername',
        'submit' => 'Absenden',
        'discussion_rooms' => 'Diskussionsräume',
        'create_room' => 'Raum erstellen',
        'room_name' => 'Raumname',
        'room_description' => 'Beschreibung (optional)',
        'join_room' => 'Beitreten',
        'send_message' => 'Senden',
        'upload_file' => 'Datei hochladen',
        'emoji' => 'Emoji',
        'type_message' => 'Nachricht schreiben...',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'delete_confirm' => 'Diese Nachricht wirklich löschen?',
        'edit_prompt' => 'Nachricht bearbeiten:',
        'file_upload_error' => 'Fehler beim Hochladen',
        'login_error' => 'Ungültige Anmeldedaten',
        'register_error' => 'Registrierung fehlgeschlagen',
        'register_success' => 'Registrierung erfolgreich! Bitte anmelden.',
        'settings_saved' => 'Einstellungen gespeichert!',
        'no_messages' => 'Keine Nachrichten. Schreiben Sie die erste Nachricht!',
        'online' => 'Online',
        'members' => 'Mitglieder',
        'dashboard' => 'Dashboard',
        'welcome' => 'Willkommen',
    ]
];

function __($key) {
    global $translations;
    $lang = isset($_SESSION['user_language']) ? $_SESSION['user_language'] : 'en';
    return $translations[$lang][$key] ?? $key;
}

function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit;
}

function getUserSettings($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT theme, language FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function updateUserThemeLanguage($pdo, $user_id, $theme, $language) {
    $stmt = $pdo->prepare("UPDATE users SET theme = ?, language = ? WHERE id = ?");
    return $stmt->execute([$theme, $language, $user_id]);
}
?>  