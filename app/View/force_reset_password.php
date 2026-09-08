<?php
/**
 * View: Force Password Reset (First Login)
 *
 * Displayed when a user logs in for the first time and is required to set a
 * personal password before proceeding. Shows optional error and success
 * feedback messages and renders a password confirmation form that posts to
 * index.php?page=force-reset-password. Applies the tritanopia accessibility
 * CSS class when the corresponding session preference is active.
 *
 * @var string|null $error   Error message to display, or null if none
 * @var string|null $success Success message to display, or null if none
 */
$isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Première Connexion - Aix-Marseille University</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>
</head>
<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">
<div class="container">

    <h2>Première Connexion<br>Aix-Marseille University</h2>

    <p class="security-notice">
        Pour des raisons de sécurité, vous devez personnaliser votre mot de passe lors de votre première connexion.
    </p>

    <?php if (!empty($error)) : ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)) : ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=force-reset-password">
        <label for="password">Nouveau mot de passe (8 caractères min.)</label>
        <input type="password" name="password" id="password" required minlength="12" placeholder="Minimum 12 caractères">

        <label for="password_confirm">Confirmez le mot de passe</label>
        <input type="password" name="password_confirm" id="password_confirm" required placeholder="Confirmez votre mot de passe">

        <button type="submit" name="submit_reset">Enregistrer et me connecter</button>
        <a href="index.php?page=login" class="back-link">Retour à la connexion</a>
    </form>

    <div class="info-section warning">
        <p>Fermez votre navigateur après usage pour des raisons de sécurité.</p>
    </div>

</div>
</body>
</html>