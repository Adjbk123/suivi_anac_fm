-- =====================================================
-- MIGRATION POUR LES NOUVEAUX STATUTS
-- =====================================================

-- Création des tables si elles n'existent pas
CREATE TABLE IF NOT EXISTS statut_activite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT,
    couleur VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS statut_participation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT,
    couleur VARCHAR(20) NOT NULL
);

-- =====================================================
-- DONNÉES DE BASE POUR LES STATUTS
-- =====================================================

-- Insertion des statuts d'activité (Formations et Missions)
INSERT INTO statut_activite (code, libelle, description, couleur) VALUES
('prevue_non_executee', 'Prévue non exécutée', 'La formation/mission était planifiée, mais jamais réalisée', 'warning'),
('prevue_executee', 'Prévue exécutée', 'La formation/mission a bien eu lieu comme prévu', 'success'),
('non_prevue_executee', 'Non prévue exécutée', 'La formation/mission n''était pas prévue dans le planning initial, mais a quand même été réalisée', 'info'),
('annulee', 'Annulée', 'La formation/mission était planifiée mais a été officiellement annulée', 'danger')
ON DUPLICATE KEY UPDATE
libelle = VALUES(libelle),
description = VALUES(description),
couleur = VALUES(couleur);

-- Insertion des statuts de participation (UserFormation et UserMission)
INSERT INTO statut_participation (code, libelle, description, couleur) VALUES
('inscrit', 'Inscrit', 'L''utilisateur était prévu comme participant', 'primary'),
('participe', 'Participe', 'L''utilisateur a effectivement participé', 'success'),
('absent', 'Absent', 'L''utilisateur était prévu mais ne s''est pas présenté', 'danger'),
('non_prevus_participe', 'Non prévu participe', 'Un utilisateur qui n''était pas prévu mais a quand même participé', 'info')
ON DUPLICATE KEY UPDATE
libelle = VALUES(libelle),
description = VALUES(description),
couleur = VALUES(couleur);

-- =====================================================
-- MISE À JOUR DES DONNÉES EXISTANTES
-- =====================================================

-- Ajouter les colonnes de statut si elles n'existent pas
ALTER TABLE formation ADD COLUMN IF NOT EXISTS statut_activite_id INT;
ALTER TABLE mission ADD COLUMN IF NOT EXISTS statut_activite_id INT;
ALTER TABLE user_formation ADD COLUMN IF NOT EXISTS statut_participation_id INT;
ALTER TABLE user_mission ADD COLUMN IF NOT EXISTS statut_participation_id INT;

-- Ajouter les contraintes de clés étrangères si elles n'existent pas
ALTER TABLE formation ADD CONSTRAINT IF NOT EXISTS fk_formation_statut_activite 
    FOREIGN KEY (statut_activite_id) REFERENCES statut_activite(id);

ALTER TABLE mission ADD CONSTRAINT IF NOT EXISTS fk_mission_statut_activite 
    FOREIGN KEY (statut_activite_id) REFERENCES statut_activite(id);

ALTER TABLE user_formation ADD CONSTRAINT IF NOT EXISTS fk_user_formation_statut_participation 
    FOREIGN KEY (statut_participation_id) REFERENCES statut_participation(id);

ALTER TABLE user_mission ADD CONSTRAINT IF NOT EXISTS fk_user_mission_statut_participation 
    FOREIGN KEY (statut_participation_id) REFERENCES statut_participation(id);

-- Mettre à jour les formations existantes avec le statut par défaut
UPDATE formation SET statut_activite_id = (SELECT id FROM statut_activite WHERE code = 'prevue_non_executee') WHERE statut_activite_id IS NULL;

-- Mettre à jour les missions existantes avec le statut par défaut
UPDATE mission SET statut_activite_id = (SELECT id FROM statut_activite WHERE code = 'prevue_non_executee') WHERE statut_activite_id IS NULL;

-- Mettre à jour les user_formations existantes avec le statut par défaut
UPDATE user_formation SET statut_participation_id = (SELECT id FROM statut_participation WHERE code = 'inscrit') WHERE statut_participation_id IS NULL;

-- Mettre à jour les user_missions existantes avec le statut par défaut
UPDATE user_mission SET statut_participation_id = (SELECT id FROM statut_participation WHERE code = 'inscrit') WHERE statut_participation_id IS NULL;

-- =====================================================
-- SUPPRESSION DES ANCIENNES COLONNES (optionnel)
-- =====================================================

-- Décommenter ces lignes si vous voulez supprimer les anciennes colonnes de statut
-- ALTER TABLE formation DROP COLUMN IF EXISTS statut;
-- ALTER TABLE mission DROP COLUMN IF EXISTS statut;
-- ALTER TABLE user_formation DROP COLUMN IF EXISTS statut_execution;
-- ALTER TABLE user_mission DROP COLUMN IF EXISTS statut_execution;
