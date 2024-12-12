<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241122085224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station CHANGE latitude latitude NUMERIC(20, 5) DEFAULT NULL, CHANGE longitude longitude NUMERIC(20, 5) DEFAULT NULL, CHANGE exact_latitude exact_latitude NUMERIC(20, 5) NOT NULL, CHANGE exact_longitude exact_longitude NUMERIC(20, 5) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station CHANGE latitude latitude NUMERIC(8, 5) DEFAULT NULL, CHANGE longitude longitude NUMERIC(8, 5) DEFAULT NULL, CHANGE exact_latitude exact_latitude NUMERIC(8, 5) NOT NULL, CHANGE exact_longitude exact_longitude NUMERIC(8, 5) NOT NULL');
    }
}
