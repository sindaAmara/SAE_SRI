<?php
/**
 * Partial: Site Footer
 *
 * Renders a minimal footer with a copyright line and conditional navigation links.
 *
 * ── ROLE RESOLUTION ──────────────────────────────────────────────────────────
 * $userRole is resolved via a null-coalescing assignment if not already set by
 * the including layout:
 * 1. If $_SESSION['user_role'] === 'admin' → 'admin'.
 * 2. Else if $_SESSION['numetu'] is present → 'student'.
 * 3. Otherwise → null.
 * This fallback allows the footer to be included from layouts that do not
 * inject $userRole (e.g. base_minimal.php does not set it before including).
 *
 * ── LINKS ────────────────────────────────────────────────────────────────────
 * - "Mentions Légales & RGPD / Legal Notice & GDPR" : always shown for all roles.
 * - "Plan du site 🗺️ / Site Map 🗺️"                 : shown only when $userRole
 *   is 'admin' or 'student'; hidden for coordinators, department heads, and
 *   unauthenticated visitors.
 *
 * @var string|null  $userRole  Role of the current user; may be null for unauthenticated pages — resolved locally if not injected
 * @var string       $lang      Current language code (e.g. 'fr' or 'en')
 * @var callable     $t         Translation callable — accepts ['fr' => '...', 'en' => '...']
 */

$userRole ??= (
isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'
    ? 'admin'
    : (isset($_SESSION['numetu']) ? 'student' : null)
);
?>
<footer>
    <p>&copy; 2026 - Aix-Marseille Université</p>

    <div class="footer-links">
        <a href="index.php?page=mentions-legales&lang=<?= htmlspecialchars($lang) ?>" class="footer-sitemap-btn no-icon">
            <?= $t(['fr' => 'Mentions Légales & RGPD', 'en' => 'Legal Notice & GDPR']) ?>
        </a>

        <?php if ($userRole === 'admin' || $userRole === 'student'): ?>
            <a href="index.php?page=web_plan&lang=<?= htmlspecialchars($lang) ?>" class="footer-sitemap-btn">
                <?= htmlspecialchars($t(['fr' => 'Plan du site 🗺️', 'en' => 'Site Map 🗺️'])) ?>
            </a>
        <?php endif; ?>
    </div>
</footer>