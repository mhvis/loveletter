<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240222095527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game (code BLOB NOT NULL --(DC2Type:uuid)
        , group_size INTEGER NOT NULL, player1 BLOB DEFAULT NULL --(DC2Type:uuid)
        , player2 BLOB DEFAULT NULL --(DC2Type:uuid)
        , player3 BLOB DEFAULT NULL --(DC2Type:uuid)
        , player4 BLOB DEFAULT NULL --(DC2Type:uuid)
        , state CLOB NOT NULL --(DC2Type:json)
        , PRIMARY KEY(code))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318CBE9C631A ON game (player1)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C279532A0 ON game (player2)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C50920236 ON game (player3)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318CCEF69795 ON game (player4)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game');
    }
}
