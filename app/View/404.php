<?php
/**
 * View: 404 Not Found
 *
 * Displays a user-friendly error page when the requested route or resource
 * does not exist. Applies the tritanopia accessibility CSS class when the
 * corresponding session preference is active.
 *
 * @var string $titre       Page title injected by the controller
 * @var bool   $isTritanopia Whether the tritanopia colour-blindness mode is enabled
 */
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/404.css">
    <title><?= htmlspecialchars($titre) ?> - 404</title>
</head>
<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">
    <div class="notfound-container">
        <h1>404</h1>
        <p>La page que vous recherchez n’existe pas.</p>
        <a href="/">Retour à l’accueil</a>
    </div>
</body>
</html>