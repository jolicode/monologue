<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210426140411 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE amnesty (id UUID NOT NULL, date DATE NOT NULL, user_ids JSON NOT NULL, redeemed BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN amnesty.date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE amnesty');
    }
}
