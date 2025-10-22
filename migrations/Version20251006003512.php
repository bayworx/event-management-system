<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006003512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE administrators (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, is_super_admin TINYINT(1) DEFAULT 0 NOT NULL, department VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_73A716FE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE attendees (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, organization VARCHAR(255) DEFAULT NULL, job_title VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, email_verified_at DATETIME DEFAULT NULL, is_checked_in TINYINT(1) DEFAULT 0 NOT NULL, checked_in_at DATETIME DEFAULT NULL, registered_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, badge_data VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_C8C96B25E7927C74 (email), INDEX IDX_C8C96B2571F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, max_attendees INT DEFAULT NULL, banner_image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_3BAE0AA7989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_administrators (event_id INT NOT NULL, administrator_id INT NOT NULL, INDEX IDX_D87EC71C71F7E88B (event_id), INDEX IDX_D87EC71C4B09E92C (administrator_id), PRIMARY KEY(event_id, administrator_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_files (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, file_size BIGINT NOT NULL, download_count INT DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, uploaded_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX IDX_472EF17571F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attendees ADD CONSTRAINT FK_C8C96B2571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_administrators ADD CONSTRAINT FK_D87EC71C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_administrators ADD CONSTRAINT FK_D87EC71C4B09E92C FOREIGN KEY (administrator_id) REFERENCES administrators (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_files ADD CONSTRAINT FK_472EF17571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendees DROP FOREIGN KEY FK_C8C96B2571F7E88B');
        $this->addSql('ALTER TABLE event_administrators DROP FOREIGN KEY FK_D87EC71C71F7E88B');
        $this->addSql('ALTER TABLE event_administrators DROP FOREIGN KEY FK_D87EC71C4B09E92C');
        $this->addSql('ALTER TABLE event_files DROP FOREIGN KEY FK_472EF17571F7E88B');
        $this->addSql('DROP TABLE administrators');
        $this->addSql('DROP TABLE attendees');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_administrators');
        $this->addSql('DROP TABLE event_files');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
