<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126205540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C8C96B25E7927C74 ON attendees');
        $this->addSql('CREATE UNIQUE INDEX unique_email_per_event ON attendees (email, event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_email_per_event ON attendees');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8C96B25E7927C74 ON attendees (email)');
    }
}
