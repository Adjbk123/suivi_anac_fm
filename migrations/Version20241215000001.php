<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241215000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dureeReelle and budgetReel fields to formation table if they do not exist';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les colonnes existent déjà avant de les ajouter
        $this->addSql('ALTER TABLE formation ADD COLUMN IF NOT EXISTS duree_reelle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE formation ADD COLUMN IF NOT EXISTS budget_reel DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes si elles existent
        $this->addSql('ALTER TABLE formation DROP COLUMN IF EXISTS duree_reelle');
        $this->addSql('ALTER TABLE formation DROP COLUMN IF EXISTS budget_reel');
    }
}
