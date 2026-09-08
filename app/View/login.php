<?php
/**
 * View: Login / Password Reset
 *
 * Multi-mode authentication view that renders one of three forms depending on
 * the boolean flags provided by the controller:
 *
 * - $isTokenReset: password reset form using a one-time token (token-based reset flow)
 * - $isReset:      email form to request a password-reset link
 * - $isLogin:      standard login form (identifier + password)
 *
 * Displays a feedback message when $message is non-empty, styled as 'success'
 * when the message contains the word "succès", otherwise as 'error'.
 * Applies the tritanopia accessibility CSS class when the corresponding
 * session preference is active.
 *
 * @var string      $message      Feedback message to display (may be empty)
 * @var bool        $isTokenReset Whether the token-based password reset form should be shown
 * @var string|null $token        One-time reset token to embed in the token-reset form, or null
 * @var bool        $isReset      Whether the email-based reset request form should be shown
 * @var bool        $isLogin      Whether the standard login form should be shown
 */

$isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aix-Marseille University</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">
<div class="container">

    <h2>Authentication Service<br>Aix-Marseille University</h2>

    <?php if (!empty($message)) : ?>
        <div class="message <?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($isTokenReset) : ?>
        <form method="POST" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" required>
            <button type="submit" name="action" value="reset_password">Valider</button>
        </form>

    <?php elseif ($isReset) : ?>
        <form method="POST" action="">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
            <button type="submit" name="action" value="request_reset">Envoyer le lien</button>
            <a href="index.php?page=login" style="display:block;margin-top:10px;">Retour</a>
        </form>

    <?php elseif ($isLogin) : ?>
        <form method="POST" action="">
            <label for="identifier">Identifiant / Email</label>
            <input type="text" name="identifier" id="identifier" required>

            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" name="action" value="login">Se connecter</button>
            <a href="index.php?page=forgot_password" style="display:block;margin-top:10px;">Mot de passe oublié ?</a>
        </form>
    <?php endif; ?>

    <div class="info-section warning">
        <p>Fermez votre navigateur après usage pour des raisons de sécurité.</p>
    </div>

    <img src="img/logo_amu_login.png" alt="Aix-Marseille University Logo" class="logo-amu">

</div>
</body>
</html>