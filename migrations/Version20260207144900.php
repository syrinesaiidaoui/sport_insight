<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add auteur_anonyme column to commentaire table
 */
final class Version20260207144900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add auteur_anonyme column to commentaire table for anonymous commenters';
    }

    public function up(Schema $schema): void
    {
        // Check if column already exists to avoid duplicate column error
        $table = $schema->getTable('commentaire');
        if (!$table->hasColumn('auteur_anonyme')) {
            $this->addSql('ALTER TABLE commentaire ADD auteur_anonyme VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP COLUMN auteur_anonyme');
    }
}
