<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on sponsor name';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sponsor ADD CONSTRAINT UNIQUE_sponsor_nom UNIQUE (nom)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sponsor DROP INDEX UNIQUE_sponsor_nom');
    }
}
