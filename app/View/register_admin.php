<?php
/**
 * View: Create Administrator Account
 *
 * Restricted form that allows the creation of a new administrator account.
 * Displays a feedback message styled with the CSS class provided by
 * $messageType when $message is non-empty. The form posts last name, first
 * name, email, and password to index.php?page=register_admin.
 *
 * @var string $message     Feedback message to display (may be empty)
 * @var string $messageType CSS class applied to the feedback message element (e.g. 'success', 'error')
 */
?>
<!DOCTYPE html><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Administrator</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="styles/register.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>
</head>

<body>
<div class="admin-header">
    <h1>Administrator Area</h1>
    <p>Create a new administrator account</p>
</div>

<div class="container">
    <?php if (!empty($message)) : ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <h2 class="register-title">Create Administrator Account</h2>
    <p class="warning-text">
        <strong>Warning:</strong> This account will have access to all administration functionalities.
    </p>

    <form class="register-form" method="POST" action="index.php?page=register_admin">
        <input type="hidden" name="action" value="register_admin">

        <input type="text" name="nom" placeholder="Last Name" required>
        <input type="text" name="prenom" placeholder="First Name" required>
        <input type="email" name="email" placeholder="Administrator Email" required>
        <input type="password" name="password" placeholder="Password (min. 8 characters)" required minlength="8">

        <button type="submit" class="btn-submit">Create Administrator</button>
    </form>

    <div class="toggle">
        <a href="index.php?page=dashboard" class="back-link">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>