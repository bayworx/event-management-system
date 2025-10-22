<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006222756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_imports table to track import jobs for event data';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_imports (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, filename VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, import_type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, results JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', errors JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', total_rows INT DEFAULT 0 NOT NULL, successful_rows INT DEFAULT 0 NOT NULL, failed_rows INT DEFAULT 0 NOT NULL, imported_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_FDC244B3B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_imports ADD CONSTRAINT FK_FDC244B3B03A8386 FOREIGN KEY (created_by_id) REFERENCES administrators (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_imports DROP FOREIGN KEY FK_FDC244B3B03A8386');
        $this->addSql('DROP TABLE event_imports');
    }
}
