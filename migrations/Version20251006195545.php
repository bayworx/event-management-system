<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006195545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, event_id INT NOT NULL, reply_to_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, sent_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, INDEX IDX_DB021E96F624B39D (sender_id), INDEX IDX_DB021E96E92F8F78 (recipient_id), INDEX IDX_DB021E9671F7E88B (event_id), INDEX IDX_DB021E96FFDF7169 (reply_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES attendees (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E92F8F78 FOREIGN KEY (recipient_id) REFERENCES administrators (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E9671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96FFDF7169 FOREIGN KEY (reply_to_id) REFERENCES messages (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E92F8F78');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E9671F7E88B');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96FFDF7169');
        $this->addSql('DROP TABLE messages');
    }
}
