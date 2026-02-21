<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order item table and enrich order with contact/address/total fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD contact_email VARCHAR(180) DEFAULT NULL, ADD contact_phone VARCHAR(30) DEFAULT NULL, ADD shipping_address LONGTEXT DEFAULT NULL, ADD billing_address LONGTEXT DEFAULT NULL, ADD total_amount NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE product_id product_id INT DEFAULT NULL, CHANGE quantity quantity INT DEFAULT NULL');

        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, order_ref_id INT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, INDEX IDX_52EA1F0984584665A (product_id), INDEX IDX_52EA1F0EA35A9E0D (order_ref_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F0984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F0EA35A9E0D FOREIGN KEY (order_ref_id) REFERENCES `order` (id) ON DELETE CASCADE');

        // Backfill contact and totals for existing orders
        $this->addSql('UPDATE `order` o LEFT JOIN user u ON u.id = o.entraineur_id SET o.contact_email = COALESCE(o.contact_email, u.email), o.contact_phone = COALESCE(o.contact_phone, u.telephone)');
        $this->addSql('UPDATE `order` o LEFT JOIN product p ON p.id = o.product_id SET o.total_amount = COALESCE(o.total_amount, (COALESCE(o.quantity, 0) * COALESCE(p.price, 0)))');

        // Create one order item per legacy order line if missing
        $this->addSql('INSERT INTO order_item (product_id, order_ref_id, quantity, unit_price)
            SELECT o.product_id, o.id, COALESCE(o.quantity, 1), COALESCE(p.price, 0)
            FROM `order` o
            INNER JOIN product p ON p.id = o.product_id
            LEFT JOIN order_item oi ON oi.order_ref_id = o.id
            WHERE o.product_id IS NOT NULL AND oi.id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F0984584665A');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F0EA35A9E0D');
        $this->addSql('DROP TABLE order_item');

        $this->addSql('ALTER TABLE `order` CHANGE product_id product_id INT NOT NULL, CHANGE quantity quantity INT NOT NULL');
        $this->addSql('ALTER TABLE `order` DROP contact_email, DROP contact_phone, DROP shipping_address, DROP billing_address, DROP total_amount');
    }
}
