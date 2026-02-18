<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218140100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image column to equipe table for team logo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe ADD COLUMN image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP COLUMN image');
    }
}
