<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191213140741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, status SMALLINT NOT NULL, name VARCHAR(255) DEFAULT NULL, display_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, seen_at DATETIME DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE individu (id INT AUTO_INCREMENT NOT NULL, espece_id INT NOT NULL, station_id INT NOT NULL, user_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, INDEX IDX_5EE42FCE2D191E7A (espece_id), INDEX IDX_5EE42FCE21BDB235 (station_id), INDEX IDX_5EE42FCEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE observation (id INT AUTO_INCREMENT NOT NULL, individu_id INT NOT NULL, evenement_id INT NOT NULL, user_id INT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, INDEX IDX_C576DBE0480B6028 (individu_id), INDEX IDX_C576DBE0FD02F13 (evenement_id), INDEX IDX_C576DBE0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_espece (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, reigne enum(\'animaux\', \'plantes\'), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, category VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, title VARCHAR(100) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, cover VARCHAR(255) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, INDEX IDX_5A8A6C8DF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE espece (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, nom_vernaculaire VARCHAR(255) NOT NULL, nom_scientifique VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_1A2A1B1C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, is_public TINYINT(1) NOT NULL, slug VARCHAR(100) NOT NULL, locality VARCHAR(100) NOT NULL, habitat VARCHAR(100) NOT NULL, header_image VARCHAR(255) DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, latitude NUMERIC(8, 5) NOT NULL, longitude NUMERIC(8, 5) NOT NULL, INDEX IDX_9F39F8B1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evenement_espece (id INT AUTO_INCREMENT NOT NULL, evenement_id INT NOT NULL, espece_id INT NOT NULL, index_unique_id INT NOT NULL, description LONGTEXT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, INDEX IDX_B2577133FD02F13 (evenement_id), INDEX IDX_B25771332D191E7A (espece_id), UNIQUE INDEX UNIQ_B2577133D353D98C (index_unique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, stade_bbch INT DEFAULT NULL, description VARCHAR(100) NOT NULL, is_observable TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCE2D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id)');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCE21BDB235 FOREIGN KEY (station_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0480B6028 FOREIGN KEY (individu_id) REFERENCES individu (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE espece ADD CONSTRAINT FK_1A2A1B1C54C8C93 FOREIGN KEY (type_id) REFERENCES type_espece (id)');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE evenement_espece ADD CONSTRAINT FK_B2577133FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE evenement_espece ADD CONSTRAINT FK_B25771332D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id)');
        $this->addSql('ALTER TABLE evenement_espece ADD CONSTRAINT FK_B2577133D353D98C FOREIGN KEY (index_unique_id) REFERENCES espece (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCEA76ED395');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0A76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1A76ED395');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0480B6028');
        $this->addSql('ALTER TABLE espece DROP FOREIGN KEY FK_1A2A1B1C54C8C93');
        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCE2D191E7A');
        $this->addSql('ALTER TABLE evenement_espece DROP FOREIGN KEY FK_B25771332D191E7A');
        $this->addSql('ALTER TABLE evenement_espece DROP FOREIGN KEY FK_B2577133D353D98C');
        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCE21BDB235');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE0FD02F13');
        $this->addSql('ALTER TABLE evenement_espece DROP FOREIGN KEY FK_B2577133FD02F13');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE individu');
        $this->addSql('DROP TABLE observation');
        $this->addSql('DROP TABLE type_espece');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE espece');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE evenement_espece');
        $this->addSql('DROP TABLE evenement');
    }
}
