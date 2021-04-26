<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190903163028 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event (id UUID NOT NULL, type VARCHAR(255) NOT NULL, content TEXT NOT NULL, author VARCHAR(255) NOT NULL, created_at TIMESTAMP(3) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN event.created_at IS \'(DC2Type:datetime_immutable_ms)\'');
        $this->addSql('CREATE TABLE debt (id UUID NOT NULL, event_id UUID NOT NULL, cause_id UUID NOT NULL, author VARCHAR(255) NOT NULL, created_at DATE NOT NULL, paid BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DBBF0A8371F7E88B ON debt (event_id)');
        $this->addSql('CREATE INDEX IDX_DBBF0A8366E2221E ON debt (cause_id)');
        $this->addSql('COMMENT ON COLUMN debt.created_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE debt ADD CONSTRAINT FK_DBBF0A8371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE debt ADD CONSTRAINT FK_DBBF0A8366E2221E FOREIGN KEY (cause_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE debt DROP CONSTRAINT FK_DBBF0A8371F7E88B');
        $this->addSql('ALTER TABLE debt DROP CONSTRAINT FK_DBBF0A8366E2221E');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE debt');
    }
}
