<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010203006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add featured_events table for rotating banners/ads functionality';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE featured_events (id INT AUTO_INCREMENT NOT NULL, related_event_id INT DEFAULT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, link_url VARCHAR(500) DEFAULT NULL, link_text VARCHAR(100) DEFAULT NULL, priority INT NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, display_type VARCHAR(50) DEFAULT \'banner\' NOT NULL, display_settings JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', view_count INT NOT NULL, click_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_430464EBD774A626 (related_event_id), INDEX IDX_430464EBB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE featured_events ADD CONSTRAINT FK_430464EBD774A626 FOREIGN KEY (related_event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE featured_events ADD CONSTRAINT FK_430464EBB03A8386 FOREIGN KEY (created_by_id) REFERENCES administrators (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE featured_events DROP FOREIGN KEY FK_430464EBD774A626');
        $this->addSql('ALTER TABLE featured_events DROP FOREIGN KEY FK_430464EBB03A8386');
        $this->addSql('DROP TABLE featured_events');
    }
}
