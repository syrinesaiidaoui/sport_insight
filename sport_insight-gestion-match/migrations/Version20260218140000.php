<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove id_equipe column from equipe table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP COLUMN id_equipe');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe ADD COLUMN id_equipe VARCHAR(100) NOT NULL');
    }
}
