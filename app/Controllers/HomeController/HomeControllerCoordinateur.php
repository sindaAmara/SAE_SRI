<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use PDOException;
use Model\UseCase\GetAdminStatsUseCase;
use Core\View;

/**
 * Controller responsible for the coordinator dashboard.
 * It retrieves global statistics about student folders,
 * applies filters (mobility type and department),
 * and renders the coordinator homepage.
 */
class HomeControllerCoordinateur implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return $page === 'home-coordinateur';
    }

    /** Valeur de filtre qui ne correspond à aucun dossier (compte sans département). */
    public const AUCUN_DEPARTEMENT = '__aucun_departement__';

    /**
     * Les 3 cases de l'accueil du profil Département.
     *
     * - Incomplets (étape 1)
     * - À valider par moi : coordinateur → dossiers validés par le secrétariat (étape 3),
     *                       chef de dépt → dossiers validés par le coordinateur (étape 4)
     * - Validés (étape 5, vert)
     *
     * Chaque case pointe vers la (ou les) liste(s) du rôle, filtrée(s) sur l'étape.
     *
     * @return array<int, array{etape: string, label: array{fr: string, en: string}, count: int, class: string, links: array<int, array{page: string, label: array{fr: string, en: string}}>}>
     */
    public static function buildWorkflowTiles(string $role, ?string $mobilite, \Model\Entity\WorkflowStats $ws): array
    {
        $isChef = $role === 'chef_departement';

        // Page(s) de liste du rôle
        $etude = ['page' => 'coordinateur-etude', 'label' => ['fr' => 'Études', 'en' => 'Studies']];
        $stage = ['page' => 'coordinateur-stage', 'label' => ['fr' => 'Stages', 'en' => 'Internships']];
        $links = match (true) {
            $isChef                         => [['page' => 'chef-departement', 'label' => ['fr' => 'Voir', 'en' => 'View']]],
            $role === 'coordinateur_etude'  => [$etude],
            $role === 'coordinateur_stage'  => [$stage],
            $mobilite === 'etude'           => [$etude],
            $mobilite === 'stage'           => [$stage],
            default                         => [$etude, $stage], // coordinateur études + stages
        };

        return [
            [
                'etape' => \Model\Entity\WorkflowStep::INCOMPLET,
                'label' => ['fr' => 'Dossiers incomplets', 'en' => 'Incomplete folders'],
                'count' => $ws->getIncomplete(),
                'class' => 'incomplete',
                'links' => $links,
            ],
            [
                'etape' => $isChef ? \Model\Entity\WorkflowStep::VALIDE_COORDINATEUR : \Model\Entity\WorkflowStep::VALIDE_SECRETARIAT,
                'label' => ['fr' => 'À valider par moi', 'en' => 'Awaiting my validation'],
                'count' => $isChef ? $ws->getValidatedAdminAndCoordinateur() : $ws->getValidatedAdmin(),
                'class' => 'a-valider-moi',
                'links' => $links,
            ],
            [
                'etape' => \Model\Entity\WorkflowStep::VALIDE_CHEF,
                'label' => ['fr' => 'Dossiers validés', 'en' => 'Validated folders'],
                'count' => $ws->getValidatedChefDepartement(),
                'class' => 'complete',
                'links' => $links,
            ],
        ];
    }

    /**
     * Starts the session if it is not already active.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirects the user to a specific URL.
     * Protected to allow mocking during testing.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renders a view.
     *
     * @param array<string, mixed> $data Data passed to the view
     */
    protected function renderView(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Writes a message to the PHP error log.
     */
    protected function log(string $message): void
    {
        error_log($message);
    }

    /**
     * Creates the use case instance used to retrieve statistics.
     * Can be overridden during testing.
     */
    protected function makeUseCase(): GetAdminStatsUseCase
    {
        $repo = new \Model\Persistence\FolderRepositoryPDO();
        return new GetAdminStatsUseCase($repo);
    }

    /**
     * Retrieves the list of all departments.
     *
     * @return array<int, string>
     */
    protected function fetchDepartements(): array
    {
        return (new \Model\Persistence\FolderRepositoryPDO())->getAllDepartements();
    }

    /**
     * Main controller logic for the coordinator dashboard.
     * Handles authentication, filters, statistics retrieval,
     * and rendering the coordinator homepage.
     */
    public function control(): void
    {
        $this->startSession();

        // Allowed roles that can access the coordinator dashboard
        $allowedRoles = ['coordinateur', 'coordinateur_etude', 'coordinateur_stage', 'chef_departement'];
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $lang = $_SESSION['lang'] ?? 'fr';

        // Handle accessibility option (tritanopia color mode)
        if (isset($_GET['tritanopia'])) {
            $_SESSION['tritanopia'] = (strval($_GET['tritanopia']) === '1');
        }

        $role = strval($_SESSION['role']);

        // Mobility filter (study or internship).
        // Un coordinateur études ne voit que les études, un coordinateur stage que les stages (CDC §9.2.2).
        $mobiliteFilter = null;
        if (isset($_GET['mobilite']) && in_array($_GET['mobilite'], ['etude', 'stage'], true)) {
            $mobiliteFilter = $_GET['mobilite'];
        }
        if ($role === 'coordinateur_etude') $mobiliteFilter = 'etude';
        if ($role === 'coordinateur_stage') $mobiliteFilter = 'stage';

        // Department: always the one of the logged-in user (CDC §7.2 « leur département uniquement »).
        // Le paramètre ?departement= de l'URL est ignoré. Sans département sur le compte : aucun dossier.
        $userDepartement   = trim(strval($_SESSION['departement'] ?? ''));
        $departementFilter = $userDepartement !== '' ? $userDepartement : self::AUCUN_DEPARTEMENT;

        // Retrieve all departments
        $allDepartements = [];
        try {
            $allDepartements = $this->fetchDepartements();
        } catch (PDOException $e) {
            $this->log('HomeControllerCoordinateur - getAllDepartements Error: ' . $e->getMessage());
        }

        $stats                = null;
        $completionPercentage = 0.0;
        $workflowGroups       = [];

        try {
            $useCase = $this->makeUseCase();
            $stats   = $useCase->execute($mobiliteFilter, $departementFilter);

            // « Mes dossiers » : incomplets / à valider par moi / validés.
            // Coordinateur études + stages sans filtre : un bloc Études et un bloc Stages,
            // pour qu'il sache tout de suite dans quelle liste se trouvent les dossiers.
            if ($role === 'coordinateur' && $mobiliteFilter === null) {
                $blocs = ['etude' => ['fr' => 'Études', 'en' => 'Studies'], 'stage' => ['fr' => 'Stages', 'en' => 'Internships']];
                foreach ($blocs as $mobilite => $titre) {
                    $workflowGroups[] = [
                        'titre' => $titre,
                        'tiles' => self::buildWorkflowTiles($role, $mobilite, $useCase->execute($mobilite, $departementFilter)->getWorkflowStats()),
                    ];
                }
            } else {
                $workflowGroups[] = ['titre' => null, 'tiles' => self::buildWorkflowTiles($role, $mobiliteFilter, $stats->getWorkflowStats())];
            }

            // Calculate folder completion percentage
            $dossierStats         = $stats->getDossierStats();
            $completionPercentage = $dossierStats->getTotal() > 0
                ? round($dossierStats->getCompleted() / $dossierStats->getTotal() * 100, 1)
                : 0.0;
        } catch (PDOException $e) {
            $this->log('HomeControllerCoordinateur Error: ' . $e->getMessage());
        }

        // Translation helper
        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        // Helper to build URLs including the language parameter
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator      = str_contains($path, '?') ? '&' : '?';
            return $path . $separator . http_build_query($params);
        };

        // Render the coordinator dashboard
        $this->renderView('HomePage/home_coordinateur', [
            'isLoggedIn'           => true,
            'lang'                 => $lang,
            'userRole'             => $_SESSION['role'],
            'completionPercentage' => $completionPercentage,
            'stats'                => $stats,
            'mobiliteFilter'       => $mobiliteFilter,
            'departementFilter'    => $departementFilter,
            'userDepartement'      => $userDepartement,
            'workflowGroups'       => $workflowGroups,
            'allDepartements'      => $allDepartements,
            't'                    => $t,
            'buildUrl'             => $buildUrl,
        ]);
    }
}