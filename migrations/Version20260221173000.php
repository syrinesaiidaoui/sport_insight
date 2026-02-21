<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment method and payment status to order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `order` ADD payment_method VARCHAR(20) DEFAULT NULL, ADD payment_status VARCHAR(20) DEFAULT NULL");
        $this->addSql("UPDATE `order` SET payment_method = 'cod' WHERE payment_method IS NULL");
        $this->addSql("UPDATE `order` SET payment_status = CASE WHEN status IN ('confirmed','shipped','delivered') THEN 'paid' ELSE 'pending' END WHERE payment_status IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP payment_method, DROP payment_status');
    }
}
