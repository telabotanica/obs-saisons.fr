<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200616104054 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event_species CHANGE percentile_5 percentile5 INT DEFAULT NULL, CHANGE percentile_95 percentile95 INT DEFAULT NULL, CHANGE percentile_25 percentile25 INT DEFAULT NULL, CHANGE percentile_75 percentile75 INT DEFAULT NULL, ADD featured_start_day INT DEFAULT NULL, ADD featured_end_day INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event_species CHANGE percentile5 percentile_5 INT DEFAULT NULL, CHANGE percentile95 percentile_95 INT DEFAULT NULL, CHANGE percentile25 percentile_25 INT DEFAULT NULL, CHANGE percentile75 percentile_75 INT DEFAULT NULL, DROP featured_start_day INT DEFAULT NULL, DROP featured_end_day INT DEFAULT NULL');
    }
}
