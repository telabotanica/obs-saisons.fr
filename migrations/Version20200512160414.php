<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200512160414 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files CHANGE user_id user_id INT DEFAULT NULL, CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE size size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE species CHANGE post_id post_id INT DEFAULT NULL, CHANGE picture picture VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE individual CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE stade_bbch stade_bbch INT DEFAULT NULL, CHANGE is_observable is_observable TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE post CHANGE author_id author_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE cover cover VARCHAR(255) DEFAULT NULL, CHANGE location location VARCHAR(100) DEFAULT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE display_name display_name VARCHAR(255) DEFAULT NULL, CHANGE seen_at seen_at DATETIME DEFAULT NULL, CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE observation CHANGE user_id user_id INT DEFAULT NULL, CHANGE picture picture VARCHAR(255) DEFAULT NULL, CHANGE details details VARCHAR(255) DEFAULT NULL, CHANGE obs_date date DATE NOT NULL');
        $this->addSql('ALTER TABLE station CHANGE user_id user_id INT DEFAULT NULL, CHANGE header_image header_image VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event CHANGE stade_bbch stade_bbch INT DEFAULT NULL, CHANGE is_observable is_observable TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE files CHANGE user_id user_id INT DEFAULT NULL, CHANGE type type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE size size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE individual CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation CHANGE user_id user_id INT DEFAULT NULL, CHANGE picture picture VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE details details VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE date obs_date DATE NOT NULL');
        $this->addSql('ALTER TABLE post CHANGE author_id author_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\', CHANGE cover cover VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE location location VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE start_date start_date DATETIME DEFAULT \'NULL\', CHANGE end_date end_date DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE species CHANGE post_id post_id INT DEFAULT NULL, CHANGE picture picture VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE station CHANGE user_id user_id INT DEFAULT NULL, CHANGE header_image header_image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE display_name display_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE seen_at seen_at DATETIME DEFAULT \'NULL\', CHANGE reset_token reset_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
    }
}
