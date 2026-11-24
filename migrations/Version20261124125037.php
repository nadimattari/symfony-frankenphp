<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Minimal mail infrastructure: one run table and one deduplicated log table.
 */
final class Version20261124125037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mail_run and email_log tables for the mail backend';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_log (id INT AUTO_INCREMENT NOT NULL, run_id INT DEFAULT NULL, dedupe_key VARCHAR(191) NOT NULL, recipient VARCHAR(255) NOT NULL, effective_recipient VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, attempts INT NOT NULL, message_id VARCHAR(255) DEFAULT NULL, error VARCHAR(2000) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EMAIL_LOG_RUN (run_id), INDEX IDX_EMAIL_LOG_STATUS (status), UNIQUE INDEX UNIQ_EMAIL_LOG_DEDUPE_KEY (dedupe_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_run (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(32) NOT NULL, status VARCHAR(16) NOT NULL, subject VARCHAR(255) DEFAULT NULL, recipient VARCHAR(255) DEFAULT NULL, total INT NOT NULL, sent INT NOT NULL, skipped INT NOT NULL, failed INT NOT NULL, message VARCHAR(2000) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_MAIL_RUN_TYPE_CREATED (type, created_at), INDEX IDX_MAIL_RUN_STATUS (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB488384E3FEC4 FOREIGN KEY (run_id) REFERENCES mail_run (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB488384E3FEC4');
        $this->addSql('DROP TABLE email_log');
        $this->addSql('DROP TABLE mail_run');
    }
}
