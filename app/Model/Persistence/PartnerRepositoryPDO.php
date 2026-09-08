<?php

namespace Model\Persistence;

use Model\Repository\PartnerRepositoryInterface;
use Model\Entity\Partner;
use Database;
use PDOException;

/**
 * Class PartnerRepositoryPDO
 * * Manages persistence for partner institutions using PDO.
 */
class PartnerRepositoryPDO implements PartnerRepositoryInterface
{
    /**
     * Adds a new partner institution to the database.
     *
     * @param Partner $partner The partner entity to persist.
     * @return void
     */
    public function addPartner(Partner $partner): void
    {
        $pdo = Database::getInstance()->getConnection();

        $stmt = $pdo->prepare("
            INSERT INTO Partenaires (continent, pays, ville, universite_institution, type)
            VALUES (:continent, :pays, :ville, :universite, :type)
        ");

        $stmt->execute([
            'continent'  => $partner->getContinent(),
            'pays'       => $partner->getCountry(),
            'ville'      => $partner->getCity(),
            'universite' => $partner->getInstitution(),
            'type'       => $partner->getType()
        ]);
    }
}