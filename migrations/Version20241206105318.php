<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241206105318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station ADD town_latitude NUMERIC(9, 6) DEFAULT NULL, ADD town_longitude NUMERIC(9, 6) DEFAULT NULL, DROP exact_latitude, DROP exact_longitude');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station ADD exact_latitude NUMERIC(9, 6) NOT NULL, ADD exact_longitude NUMERIC(9, 6) NOT NULL, DROP town_latitude, DROP town_longitude');
    }
}
