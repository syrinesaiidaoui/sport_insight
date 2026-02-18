<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add score fields for match results
 */
final class Version20260218150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add scoreEquipeDomicile and scoreEquipeExterieur columns to matchs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matchs ADD score_equipe_domicile INT DEFAULT 0');
        $this->addSql('ALTER TABLE matchs ADD score_equipe_exterieur INT DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matchs DROP COLUMN score_equipe_domicile');
        $this->addSql('ALTER TABLE matchs DROP COLUMN score_equipe_exterieur');
    }
}
