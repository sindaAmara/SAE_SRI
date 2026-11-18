-- Ajoute les coordonnees GPS necessaires a l'affichage sur la carte interactive.
-- Verifiez d'abord le nom reel de la colonne de filtrage AMU/IUT :
--   DESCRIBE Partenaires;
-- Le code livre suppose une colonne `site` (valeurs 'amu' / 'iut'), conformement
-- a la capture d'ecran phpMyAdmin fournie. Si elle s'appelle en realite `type`
-- (comme le laisse penser PartnerRepositoryPDO::addPartner()), adaptez la clause
-- WHERE site = :site dans PartnerRepositoryPDO::findGeolocatedBySite().

ALTER TABLE Partenaires
    ADD COLUMN latitude  DECIMAL(10,7) NULL AFTER continent,
    ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;