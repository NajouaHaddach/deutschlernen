<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier doublon
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed]);
                $userId = $pdo->lastInsertId();

                // Insérer stats par défaut
                $stmtStats = $pdo->prepare("INSERT INTO user_stats 
                    (user_id, level, level_up_this_month, study_time_hours, study_weekly_increase,
                     words_learned, words_today, success_rate, success_rate_change, lessons_completed)
                    VALUES (?, 'B1', 2, 48, 3, 312, 18, 87, -2, 0)");
                $stmtStats->execute([$userId]);

                $pdo->commit();

                // ✅ Connexion automatique : créer la session
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;

                // Rediriger directement vers le tableau de bord
                header('Location: ../dashboard.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur technique : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Deutsch Platform</title>
</head>
<body>
<div>
    <h2>Créer un compte</h2>
    <?php if($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <label>Nom d'utilisateur</label><br>
        <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username']??'') ?>"><br><br>
        <label>Email</label><br>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>"><br><br>
        <label>Mot de passe (min 6)</label><br>
        <input type="password" name="password" required><br><br>
        <label>Confirmer</label><br>
        <input type="password" name="confirm_password" required><br><br>
        <button type="submit">S'inscrire</button>
    </form>
    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>
</body>
</html>