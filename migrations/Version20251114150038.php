<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114150038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE depense_formation_participant (id INT AUTO_INCREMENT NOT NULL, depense_formation_id INT NOT NULL, user_formation_id INT NOT NULL, montant_prevu NUMERIC(10, 2) DEFAULT NULL, montant_reel NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_3F98805574AA9E3D (depense_formation_id), INDEX IDX_3F988055D2CC542C (user_formation_id), UNIQUE INDEX uniq_depense_formation_user (depense_formation_id, user_formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE depense_mission_participant (id INT AUTO_INCREMENT NOT NULL, depense_mission_id INT NOT NULL, user_mission_id INT NOT NULL, montant_prevu NUMERIC(10, 2) DEFAULT NULL, montant_reel NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_956B201EE4C51D17 (depense_mission_id), INDEX IDX_956B201E190B687C (user_mission_id), UNIQUE INDEX uniq_depense_mission_user (depense_mission_id, user_mission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE depense_formation_participant ADD CONSTRAINT FK_3F98805574AA9E3D FOREIGN KEY (depense_formation_id) REFERENCES depense_formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense_formation_participant ADD CONSTRAINT FK_3F988055D2CC542C FOREIGN KEY (user_formation_id) REFERENCES user_formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense_mission_participant ADD CONSTRAINT FK_956B201EE4C51D17 FOREIGN KEY (depense_mission_id) REFERENCES depense_mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense_mission_participant ADD CONSTRAINT FK_956B201E190B687C FOREIGN KEY (user_mission_id) REFERENCES user_mission (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE depense_formation_participant DROP FOREIGN KEY FK_3F98805574AA9E3D');
        $this->addSql('ALTER TABLE depense_formation_participant DROP FOREIGN KEY FK_3F988055D2CC542C');
        $this->addSql('ALTER TABLE depense_mission_participant DROP FOREIGN KEY FK_956B201EE4C51D17');
        $this->addSql('ALTER TABLE depense_mission_participant DROP FOREIGN KEY FK_956B201E190B687C');
        $this->addSql('DROP TABLE depense_formation_participant');
        $this->addSql('DROP TABLE depense_mission_participant');
    }
}
