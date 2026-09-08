<?php
/**
 * View: Legal Notice & Privacy Policy (Mentions Légales / RGPD)
 *
 * @var callable $t    Translation function
 * @var string   $lang Current language code
 */
ob_start();
?>

    <div class="legal-container">
        <h1><?= $t(['fr' => 'Mentions Légales et Politique de Confidentialité', 'en' => 'Legal Notice & Privacy Policy']) ?></h1>

        <section class="legal-section">
            <h2>1. <?= $t(['fr' => 'Éditeur du site', 'en' => 'Site Publisher']) ?></h2>
            <p>
                <?= $t([
                    'fr' => 'Conformément à la loi pour la confiance dans l\'économie numérique (LCEN), les informations concernant l\'éditeur de ce site sont les suivantes :',
                    'en' => 'In accordance with the law on confidence in the digital economy, the publisher\'s information is as follows:'
                ]) ?>
            </p>
            <ul class="legal-list">
                <li><strong>Dénomination :</strong> Aix-Marseille Université (AMU) - IUT d'Aix-Marseille</li>
                <li><strong>Forme juridique :</strong> Établissement public à caractère scientifique, culturel et professionnel (EPSCP)</li>
                <li><strong>Siège social :</strong> 413 Avenue Gaston Berger, 13625 Aix-en-Provence</li>
                <li><strong>Contact :</strong> jocelyne.vial@univ-amu.fr | <strong>Téléphone :</strong> 04 13 94 65 02</li>
            </ul>
        </section>

        <section class="legal-section">
            <h2>2. <?= $t(['fr' => 'Hébergement du site', 'en' => 'Site Hosting']) ?></h2>
            <p>
                <?= $t([
                    'fr' => 'Ce site est auto-hébergé sur un serveur privé dans le cadre d\'un projet universitaire.',
                    'en' => 'This site is self-hosted on a private server as part of a university project.'
                ]) ?>
            </p>
            <ul class="legal-list">
                <li><strong>Hébergeur :</strong> [Ton Prénom et Ton Nom]</li>
                <li><strong>Statut :</strong> <?= $t(['fr' => 'Étudiant(e) - Projet Universitaire', 'en' => 'Student - University Project']) ?></li>
                <li><strong>Localisation du serveur :</strong> Aix En Provence, France</li>
                <li><strong>Contact technique :</strong> adam.KUROPATWA-BUTTE@etu.univ-amu.fr</li>
            </ul>
        </section>

        <section class="legal-section">
            <h2>3. <?= $t(['fr' => 'Propriété intellectuelle', 'en' => 'Intellectual Property']) ?></h2>
            <p>
                <?= $t([
                    'fr' => 'L\'ensemble de ce site relève de la législation française et internationale sur le droit d\'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques (logos, photos).',
                    'en' => 'This entire site is governed by French and international legislation on copyright and intellectual property. All reproduction rights are reserved, including for downloadable documents and images (logos, photos).'
                ]) ?>
            </p>
        </section>

        <section class="legal-section">
            <h2>4. <?= $t(['fr' => 'Protection des données personnelles (RGPD)', 'en' => 'Personal Data Protection (GDPR)']) ?></h2>
            <p>
                <?= $t([
                    'fr' => 'Dans le cadre de son activité d\'instruction des dossiers de mobilité, le service des Relations Internationales traite des données à caractère personnel (identification directe et indirecte).',
                    'en' => 'As part of its mobility application processing, the International Relations office processes personal data (direct and indirect identification).'
                ]) ?>
            </p>
            <ul class="legal-list">
                <li><strong>Finalité :</strong> <?= $t(['fr' => 'Gestion administrative, suivi des mobilités internationales et communication avec les étudiants.', 'en' => 'Administrative management, tracking of international mobility, and communication with students.']) ?></li>
                <li><strong>Base légale :</strong> <?= $t(['fr' => 'Exécution d\'une mission d\'intérêt public et consentement de l\'étudiant au moment du dépôt des pièces justificatives.', 'en' => 'Execution of a public interest mission and student consent upon submitting supporting documents.']) ?></li>
                <li><strong>Durée de conservation :</strong> <?= $t(['fr' => 'Les documents (pièces d\'identité, relevés de notes) sont conservés pendant l\'année universitaire en cours, puis détruits ou archivés selon les obligations légales de l\'université.', 'en' => 'Documents (IDs, transcripts) are kept for the current academic year, then destroyed or archived according to the university\'s legal obligations.']) ?></li>
                <li><strong>Sécurité :</strong> <?= $t(['fr' => 'Les documents téléversés sont chiffrés et l\'accès est strictement limité au personnel autorisé.', 'en' => 'Uploaded documents are encrypted and access is strictly limited to authorized personnel.']) ?></li>
            </ul>
            <p>
                <?= $t([
                    'fr' => 'Conformément au RGPD, vous bénéficiez d\'un droit d\'accès, de rectification, de portabilité et d\'effacement de vos données. Pour exercer ces droits de manière simple, contactez le Délégué à la Protection des Données (DPO) :',
                    'en' => 'In accordance with the GDPR, you have the right to access, rectify, port, and erase your data. To easily exercise these rights, contact the Data Protection Officer (DPO):'
                ]) ?>
                <br><strong>Email :</strong> samuele.ANNI@univ-amu.fr
            </p>
            <p>
                <em><?= $t([
                        'fr' => 'Si vous estimez que vos droits ne sont pas respectés, vous avez la possibilité d\'introduire une réclamation auprès de la CNIL (www.cnil.fr).',
                        'en' => 'If you feel your rights are not respected, you may file a complaint with the CNIL (www.cnil.fr).'
                    ]) ?></em>
            </p>
        </section>

        <section class="legal-section">
            <h2>5. <?= $t(['fr' => 'Gestion des Cookies', 'en' => 'Cookies Management']) ?></h2>
            <p>
                <?= $t([
                    'fr' => 'Ce site utilise uniquement des cookies "techniques" (identifiants de session) strictement nécessaires pour maintenir votre connexion sécurisée et assurer le bon fonctionnement de l\'application. Ces cookies ne tracent pas votre navigation et ne requièrent pas de consentement préalable.',
                    'en' => 'This site only uses "technical" cookies (session identifiers) strictly necessary to maintain your secure connection and ensure the application functions correctly. These cookies do not track your browsing and do not require prior consent.'
                ]) ?>
            </p>
        </section>
    </div>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="<?= htmlspecialchars($_SESSION['role'] ?? 'guest') ?>"
         style="display:none;">
    </div>

<?php
$content = ob_get_clean();
$title      = $t(['fr' => 'Mentions Légales', 'en' => 'Legal Notice']);
$activeMenu = 'legal';
$hideChat   = true;
$userRole   = $_SESSION['role'] ?? 'guest';
$styles     = ['styles/homepage.css', 'styles/mentions_legales.css'];
$scripts    = [];

include __DIR__ . '/Layout/base.php';