<?php
require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(50));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $stmt = $pdo->prepare("REPLACE INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/deutschlernen/auth/reset_password.php?email=" . urlencode($email) . "&token=$token";
        $subject = "Réinitialisation mot de passe - Deutsch Platform";
        $body = "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe :\n$resetLink\n\nCe lien expire dans 15 minutes.";
        
        // Envoi d'email (assurez-vous que votre serveur XAMPP peut envoyer des mails)
        if (mail($email, $subject, $body, "From: no-reply@deutschplatform.com")) {
            $message = "Un lien de réinitialisation a été envoyé à votre adresse email.";
        } else {
            $error = "Erreur lors de l'envoi de l'email. Vérifiez la configuration du serveur mail.";
        }
    } else {
        $error = "Aucun compte trouvé avec cet email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié</title>
</head>
<body>
    <h2>Réinitialisation</h2>
    <?php if($message): ?><p style="color:green;"><?= $message ?></p><?php endif; ?>
    <?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <form method="post">
        <label>Votre email :</label><br>
        <input type="email" name="email" required><br><br>
        <button type="submit">Envoyer le lien</button>
    </form>
    <p><a href="login.php">Retour à la connexion</a></p>
</body>
</html>