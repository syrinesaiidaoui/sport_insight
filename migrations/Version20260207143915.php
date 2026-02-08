<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207143915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annonce (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, poste_recherche VARCHAR(255) NOT NULL, niveau_requis VARCHAR(255) NOT NULL, date_publication DATE NOT NULL, statut VARCHAR(255) NOT NULL, entraineur_id INT NOT NULL, INDEX IDX_F65593E5F8478A1 (entraineur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATE NOT NULL, joueur_id INT NOT NULL, annonce_id INT NOT NULL, INDEX IDX_67F068BCA9E2D76C (joueur_id), INDEX IDX_67F068BC8805AB2F (annonce_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrat_sponsor (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, montant DOUBLE PRECISION NOT NULL, sponsor_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_28429AA212F7FB51 (sponsor_id), INDEX IDX_28429AA26D861B89 (equipe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entrainement (id INT AUTO_INCREMENT NOT NULL, date_entrainement DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, type VARCHAR(255) NOT NULL, objectif LONGTEXT NOT NULL, lieu VARCHAR(255) NOT NULL, entraineur_id INT NOT NULL, INDEX IDX_A27444E5F8478A1 (entraineur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entrainement_user (entrainement_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_EB3D3F70A15E8FD (entrainement_id), INDEX IDX_EB3D3F70A76ED395 (user_id), PRIMARY KEY(entrainement_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, id_equipe VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, coach VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, note_physique DOUBLE PRECISION NOT NULL, note_technique DOUBLE PRECISION NOT NULL, note_tactique DOUBLE PRECISION NOT NULL, commentaire LONGTEXT DEFAULT NULL, entrainement_id INT NOT NULL, joueur_id INT NOT NULL, INDEX IDX_1323A575A15E8FD (entrainement_id), INDEX IDX_1323A575A9E2D76C (joueur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE matchs (id INT AUTO_INCREMENT NOT NULL, id_match VARCHAR(100) NOT NULL, date_match DATE NOT NULL, heure_debut TIME NOT NULL, lieu VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, lineup_domicile LONGTEXT NOT NULL, lineup_exterieur LONGTEXT DEFAULT NULL, equipe_domicile_id INT NOT NULL, equipe_exterieur_id INT NOT NULL, INDEX IDX_6B1E60415FE1AEAD (equipe_domicile_id), INDEX IDX_6B1E604121ECD755 (equipe_exterieur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, order_date DATE NOT NULL, status VARCHAR(20) NOT NULL, product_id INT NOT NULL, entraineur_id INT NOT NULL, INDEX IDX_F52993984584665A (product_id), INDEX IDX_F5299398F8478A1 (entraineur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, presence VARCHAR(255) NOT NULL, justification_absence LONGTEXT DEFAULT NULL, entrainement_id INT NOT NULL, joueur_id INT DEFAULT NULL, INDEX IDX_AB55E24FA15E8FD (entrainement_id), INDEX IDX_AB55E24FA9E2D76C (joueur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(255) DEFAULT NULL, price NUMERIC(10, 0) NOT NULL, stock INT NOT NULL, size VARCHAR(10) DEFAULT NULL, brand VARCHAR(30) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sponsor (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, budget DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, date_naissance DATE DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_inscription DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E5F8478A1 FOREIGN KEY (entraineur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA9E2D76C FOREIGN KEY (joueur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC8805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id)');
        $this->addSql('ALTER TABLE contrat_sponsor ADD CONSTRAINT FK_28429AA212F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id)');
        $this->addSql('ALTER TABLE contrat_sponsor ADD CONSTRAINT FK_28429AA26D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE entrainement ADD CONSTRAINT FK_A27444E5F8478A1 FOREIGN KEY (entraineur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE entrainement_user ADD CONSTRAINT FK_EB3D3F70A15E8FD FOREIGN KEY (entrainement_id) REFERENCES entrainement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entrainement_user ADD CONSTRAINT FK_EB3D3F70A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A15E8FD FOREIGN KEY (entrainement_id) REFERENCES entrainement (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A9E2D76C FOREIGN KEY (joueur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE matchs ADD CONSTRAINT FK_6B1E60415FE1AEAD FOREIGN KEY (equipe_domicile_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE matchs ADD CONSTRAINT FK_6B1E604121ECD755 FOREIGN KEY (equipe_exterieur_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398F8478A1 FOREIGN KEY (entraineur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA15E8FD FOREIGN KEY (entrainement_id) REFERENCES entrainement (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA9E2D76C FOREIGN KEY (joueur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E5F8478A1');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA9E2D76C');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC8805AB2F');
        $this->addSql('ALTER TABLE contrat_sponsor DROP FOREIGN KEY FK_28429AA212F7FB51');
        $this->addSql('ALTER TABLE contrat_sponsor DROP FOREIGN KEY FK_28429AA26D861B89');
        $this->addSql('ALTER TABLE entrainement DROP FOREIGN KEY FK_A27444E5F8478A1');
        $this->addSql('ALTER TABLE entrainement_user DROP FOREIGN KEY FK_EB3D3F70A15E8FD');
        $this->addSql('ALTER TABLE entrainement_user DROP FOREIGN KEY FK_EB3D3F70A76ED395');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575A15E8FD');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575A9E2D76C');
        $this->addSql('ALTER TABLE matchs DROP FOREIGN KEY FK_6B1E60415FE1AEAD');
        $this->addSql('ALTER TABLE matchs DROP FOREIGN KEY FK_6B1E604121ECD755');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398F8478A1');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA15E8FD');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA9E2D76C');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE contrat_sponsor');
        $this->addSql('DROP TABLE entrainement');
        $this->addSql('DROP TABLE entrainement_user');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE matchs');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE sponsor');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
