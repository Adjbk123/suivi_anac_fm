-- =====================================================
-- DONNÉES DE BASE POUR LES STATUTS
-- =====================================================

-- Insertion des statuts d'activité (Formations et Missions)
INSERT INTO statut_activite (code, libelle, description, couleur) VALUES
('prevue_non_executee', 'Prévue non exécutée', 'La formation/mission était planifiée, mais jamais réalisée', 'warning'),
('prevue_executee', 'Prévue exécutée', 'La formation/mission a bien eu lieu comme prévu', 'success'),
('non_prevue_executee', 'Non prévue exécutée', 'La formation/mission n''était pas prévue dans le planning initial, mais a quand même été réalisée', 'info'),
('annulee', 'Annulée', 'La formation/mission était planifiée mais a été officiellement annulée', 'danger');

-- Insertion des statuts de participation (UserFormation et UserMission)
INSERT INTO statut_participation (code, libelle, description, couleur) VALUES
('inscrit', 'Inscrit', 'L''utilisateur était prévu comme participant', 'primary'),
('participe', 'Participe', 'L''utilisateur a effectivement participé', 'success'),
('absent', 'Absent', 'L''utilisateur était prévu mais ne s''est pas présenté', 'danger'),
('non_prevus_participe', 'Non prévu participe', 'Un utilisateur qui n''était pas prévu mais a quand même participé', 'info');

-- =====================================================
-- MISE À JOUR DES DONNÉES EXISTANTES (si nécessaire)
-- =====================================================

-- Mettre à jour les formations existantes avec le statut par défaut
UPDATE formation SET statut_activite_id = (SELECT id FROM statut_activite WHERE code = 'prevue_non_executee') WHERE statut_activite_id IS NULL;

-- Mettre à jour les missions existantes avec le statut par défaut
UPDATE mission SET statut_activite_id = (SELECT id FROM statut_activite WHERE code = 'prevue_non_executee') WHERE statut_activite_id IS NULL;

-- Mettre à jour les user_formations existantes avec le statut par défaut
UPDATE user_formation SET statut_participation_id = (SELECT id FROM statut_participation WHERE code = 'inscrit') WHERE statut_participation_id IS NULL;

-- Mettre à jour les user_missions existantes avec le statut par défaut
UPDATE user_mission SET statut_participation_id = (SELECT id FROM statut_participation WHERE code = 'inscrit') WHERE statut_participation_id IS NULL;
