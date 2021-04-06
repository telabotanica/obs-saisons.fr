<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210402075558 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, bbch_code INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(100) NOT NULL, is_observable TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_species (event_id INT NOT NULL, species_id INT NOT NULL, description LONGTEXT DEFAULT NULL, percentile5 INT DEFAULT NULL, percentile95 INT DEFAULT NULL, percentile25 INT DEFAULT NULL, percentile75 INT DEFAULT NULL, aberration_start_day INT DEFAULT NULL, aberration_end_day INT DEFAULT NULL, featured_start_day INT DEFAULT NULL, featured_end_day INT DEFAULT NULL, INDEX IDX_20585EBD71F7E88B (event_id), INDEX IDX_20585EBDB2A1D860 (species_id), PRIMARY KEY(event_id, species_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE individual (id INT AUTO_INCREMENT NOT NULL, species_id INT NOT NULL, station_id INT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, legacy_id INT DEFAULT NULL, INDEX IDX_8793FC17B2A1D860 (species_id), INDEX IDX_8793FC1721BDB235 (station_id), INDEX IDX_8793FC17A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE observation (id INT AUTO_INCREMENT NOT NULL, individual_id INT NOT NULL, event_id INT NOT NULL, user_id INT DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, is_missing TINYINT(1) NOT NULL, details VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_C576DBE0AE271C0D (individual_id), INDEX IDX_C576DBE071F7E88B (event_id), INDEX IDX_C576DBE0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, category VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, title VARCHAR(100) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, cover VARCHAR(255) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, pdf_url VARCHAR(255) DEFAULT NULL, status SMALLINT NOT NULL, UNIQUE INDEX UNIQ_5A8A6C8D989D9B62 (slug), INDEX IDX_5A8A6C8DF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE species (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, post_id INT DEFAULT NULL, vernacular_name VARCHAR(255) NOT NULL, scientific_name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_A50FF712C54C8C93 (type_id), INDEX IDX_A50FF7124B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, is_private TINYINT(1) NOT NULL, slug VARCHAR(100) NOT NULL, locality VARCHAR(100) NOT NULL, habitat VARCHAR(100) NOT NULL, header_image VARCHAR(255) DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, latitude NUMERIC(8, 5) NOT NULL, longitude NUMERIC(8, 5) NOT NULL, altitude NUMERIC(10, 0) NOT NULL, insee_code VARCHAR(5) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, legacy_id INT DEFAULT NULL, department VARCHAR(2) NOT NULL, UNIQUE INDEX UNIQ_9F39F8B1989D9B62 (slug), INDEX IDX_9F39F8B1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_species (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, reign VARCHAR(7) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, status SMALLINT NOT NULL, name VARCHAR(255) DEFAULT NULL, display_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, seen_at DATETIME DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, locality VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, post_code VARCHAR(5) DEFAULT NULL, profile_type VARCHAR(100) DEFAULT NULL, is_newsletter_subscriber TINYINT(1) DEFAULT \'0\' NOT NULL, email_new VARCHAR(180) DEFAULT NULL, email_token VARCHAR(255) DEFAULT NULL, legacy_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_species ADD CONSTRAINT FK_20585EBD71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_species ADD CONSTRAINT FK_20585EBDB2A1D860 FOREIGN KEY (species_id) REFERENCES species (id)');
        $this->addSql('ALTER TABLE individual ADD CONSTRAINT FK_8793FC17B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id)');
        $this->addSql('ALTER TABLE individual ADD CONSTRAINT FK_8793FC1721BDB235 FOREIGN KEY (station_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE individual ADD CONSTRAINT FK_8793FC17A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0AE271C0D FOREIGN KEY (individual_id) REFERENCES individual (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE species ADD CONSTRAINT FK_A50FF712C54C8C93 FOREIGN KEY (type_id) REFERENCES type_species (id)');
        $this->addSql('ALTER TABLE species ADD CONSTRAINT FK_A50FF7124B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_species DROP FOREIGN KEY FK_20585EBD71F7E88B');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE071F7E88B');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0AE271C0D');
        $this->addSql('ALTER TABLE species DROP FOREIGN KEY FK_A50FF7124B89032C');
        $this->addSql('ALTER TABLE event_species DROP FOREIGN KEY FK_20585EBDB2A1D860');
        $this->addSql('ALTER TABLE individual DROP FOREIGN KEY FK_8793FC17B2A1D860');
        $this->addSql('ALTER TABLE individual DROP FOREIGN KEY FK_8793FC1721BDB235');
        $this->addSql('ALTER TABLE species DROP FOREIGN KEY FK_A50FF712C54C8C93');
        $this->addSql('ALTER TABLE individual DROP FOREIGN KEY FK_8793FC17A76ED395');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0A76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1A76ED395');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_species');
        $this->addSql('DROP TABLE individual');
        $this->addSql('DROP TABLE observation');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE species');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE type_species');
        $this->addSql('DROP TABLE user');
    }
}
