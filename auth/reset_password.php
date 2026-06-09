<?php
require_once '../config/db.php';

$error = '';
$success = '';

if (!isset($_GET['email']) || !isset($_GET['token'])) {
    die("Lien invalide.");
}
$email = $_GET['email'];
$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT token, expires_at FROM password_resets WHERE email = ? AND token = ?");
$stmt->execute([$email, $token]);
$reset = $stmt->fetch();

if (!$reset || strtotime($reset['expires_at']) < time()) {
    die("Ce lien a expiré ou est invalide.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $confirm = $_POST['confirm'];
    if (strlen($newPass) < 6) {
        $error = "Min 6 caractères.";
    } elseif ($newPass !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $update->execute([$hash, $email]);
        // Supprimer la demande de reset
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $success = "Mot de passe modifié. <a href='login.php'>Connectez-vous</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau mot de passe</title>
</head>
<body>
<div>
    <h2>Nouveau mot de passe</h2>
    <?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <?php if($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>
    <?php if(!$success): ?>
    <form method="post">
        <label>Nouveau mot de passe</label><br>
        <input type="password" name="password" required><br><br>
        <label>Confirmer</label><br>
        <input type="password" name="confirm" required><br><br>
        <button type="submit">Changer</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>