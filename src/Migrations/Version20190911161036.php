<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190911161036 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE debt ADD paid_at DATE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN debt.paid_at IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE debt DROP paid_at');
    }
}
