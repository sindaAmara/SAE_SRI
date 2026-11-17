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
     * NOTE: this inserts into the `type` column. If your live schema names
     * this column `site` instead (as used by findGeolocatedBySite() below),
     * this INSERT will throw a PDOException — run `DESCRIBE Partenaires;`
     * to confirm the real column name and align both methods on it.
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

    /**
     * Finds all geolocated partner institutions for the given site.
     *
     * @param string $site Partner site to filter on ('amu' or 'iut')
     * @return Partner[]
     */
    public function findGeolocatedBySite(string $site): array
    {
        $pdo = Database::getInstance()->getConnection();

        $stmt = $pdo->prepare("
            SELECT id, continent, pays, ville, universite_institution, site, latitude, longitude, offres
            FROM Partenaires
            WHERE site = :site
              AND latitude IS NOT NULL
              AND longitude IS NOT NULL
            ORDER BY pays, ville
        ");
        $stmt->execute(['site' => $site]);

        $partners = [];
        foreach ($stmt->fetchAll() as $row) {
            $partners[] = new Partner(
                (string) $row['continent'],
                (string) $row['pays'],
                (string) $row['ville'],
                (string) $row['universite_institution'],
                (string) $row['site'],
                (int) $row['id'],
                (float) $row['latitude'],
                (float) $row['longitude'],
                $row['offres'] !== null ? (string) $row['offres'] : null
            );
        }

        return $partners;
    }
}