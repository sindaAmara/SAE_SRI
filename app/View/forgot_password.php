<?php
/**
 * View: Forgot Password
 *
 * Allows a user to request a password-reset link by submitting their email
 * address. Displays a feedback message styled according to $messageType.
 * The reset form is hidden once a successful submission has been made
 * (i.e. when $messageType is 'success'). Applies the tritanopia accessibility
 * CSS class when the corresponding session preference is active.
 *
 * @var string $message     Feedback message to display (may be empty)
 * @var string $messageType CSS class / severity of the message: 'success' | 'error' | 'info'
 */
$messageType  = $messageType ?? 'info';
$isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Aix-Marseille University</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">
<div class="container">

    <h2>Réinitialisation du mot de passe<br>Aix-Marseille University</h2>

    <?php if (!empty($message)) : ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($messageType !== 'success') : ?>
        <form method="POST" action="">
            <div class="field-group email">
                <label for="email">Adresse email</label>
                <input type="email" name="email" id="email">
            </div>
            <button type="submit" name="action" value="forgot_password">
                Envoyer le lien de réinitialisation
            </button>
        </form>
    <?php endif; ?>

    <div class="toggle">
        <p><a href="index.php?page=login">← Retour à la connexion</a></p>
    </div>

    <div class="info-section help">
        <p>Un email contenant un lien de réinitialisation vous sera envoyé.<br>
            Vérifiez également vos <strong>spams</strong>.</p>
    </div>

    <div class="info-section warning">
        <p>Fermez votre navigateur après usage pour des raisons de sécurité.</p>
    </div>

    <img src="img/logo_amu_login.png" alt="Aix-Marseille University Logo" class="logo-amu">

</div>
</body>
</html>