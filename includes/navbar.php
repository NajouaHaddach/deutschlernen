<?php
$current = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
?>
<nav class="navbar">
    <div class="container nav-container">
        <a href="<?= $isLoggedIn ? '/dashboard.php' : '/index.php' ?>" class="logo">DeutschLern+</a>
        <ul class="nav-links">
            <?php if (!$isLoggedIn): ?>
                <li><a href="/index.php" class="<?= $current == 'index.php' ? 'active' : '' ?>">Start</a></li>
                <li><a href="/auth/login.php">Anmelden</a></li>
                <li><a href="/auth/register.php">Registrieren</a></li>
            <?php else: ?>
                <li><a href="/dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="/courses/index.php">Kurse</a></li>
                <li><a href="/scenarios/index.php">Szenarien</a></li>
                <li><a href="/chatbot/index.php">Chatbot</a></li>
                <li><a href="/forum/index.php">Diskussion</a></li>
                <li><a href="/auth/logout.php">Abmelden</a></li>
            <?php endif; ?>
        </ul>
        <?php if ($isLoggedIn): ?>
            <div class="user-info"><?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['user_level'] ?>)</div>
        <?php endif; ?>
    </div>
</nav>