<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024165349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD parent_event_id INT DEFAULT NULL, ADD is_recurring TINYINT(1) DEFAULT 0 NOT NULL, ADD recurrence_pattern VARCHAR(50) DEFAULT NULL, ADD recurrence_interval INT DEFAULT NULL, ADD recurrence_end_date DATETIME DEFAULT NULL, ADD recurrence_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EE3A445A ON event (parent_event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7EE3A445A');
        $this->addSql('DROP INDEX IDX_3BAE0AA7EE3A445A ON event');
        $this->addSql('ALTER TABLE event DROP parent_event_id, DROP is_recurring, DROP recurrence_pattern, DROP recurrence_interval, DROP recurrence_end_date, DROP recurrence_count');
    }
}
