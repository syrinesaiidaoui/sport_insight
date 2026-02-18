<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove id_match column, keep only id
 */
final class Version20260218160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove id_match column from matchs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matchs DROP COLUMN id_match');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matchs ADD id_match VARCHAR(100) NOT NULL');
    }
}
