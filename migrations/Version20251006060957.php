<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006060957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_presenter (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, presenter_id INT NOT NULL, presentation_title VARCHAR(255) DEFAULT NULL, presentation_description LONGTEXT DEFAULT NULL, start_time TIME DEFAULT NULL, end_time TIME DEFAULT NULL, sort_order INT NOT NULL, is_visible TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_552054F571F7E88B (event_id), INDEX IDX_552054F5DDE4C635 (presenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presenter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, linkedin VARCHAR(255) DEFAULT NULL, twitter VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_presenter ADD CONSTRAINT FK_552054F571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_presenter ADD CONSTRAINT FK_552054F5DDE4C635 FOREIGN KEY (presenter_id) REFERENCES presenter (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_presenter DROP FOREIGN KEY FK_552054F571F7E88B');
        $this->addSql('ALTER TABLE event_presenter DROP FOREIGN KEY FK_552054F5DDE4C635');
        $this->addSql('DROP TABLE event_presenter');
        $this->addSql('DROP TABLE presenter');
    }
}
