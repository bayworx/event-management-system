<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006170641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agenda_items (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, presenter_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, item_type VARCHAR(50) NOT NULL, speaker VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, is_visible TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_B126BDD371F7E88B (event_id), INDEX IDX_B126BDD3DDE4C635 (presenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agenda_items ADD CONSTRAINT FK_B126BDD371F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE agenda_items ADD CONSTRAINT FK_B126BDD3DDE4C635 FOREIGN KEY (presenter_id) REFERENCES presenter (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agenda_items DROP FOREIGN KEY FK_B126BDD371F7E88B');
        $this->addSql('ALTER TABLE agenda_items DROP FOREIGN KEY FK_B126BDD3DDE4C635');
        $this->addSql('DROP TABLE agenda_items');
    }
}
