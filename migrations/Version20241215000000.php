<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241215000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dureeReelle and budgetReel fields to mission table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission ADD duree_reelle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mission ADD budget_reel DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission DROP duree_reelle');
        $this->addSql('ALTER TABLE mission DROP budget_reel');
    }
}
