<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116125406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_usage (id SERIAL NOT NULL, user_id INT NOT NULL, item_id INT DEFAULT NULL, operation VARCHAR(50) NOT NULL, model VARCHAR(50) NOT NULL, input_tokens INT NOT NULL, output_tokens INT NOT NULL, cost NUMERIC(10, 6) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C5C6535A76ED395 ON ai_usage (user_id)');
        $this->addSql('CREATE INDEX IDX_8C5C6535126F525E ON ai_usage (item_id)');
        $this->addSql('CREATE INDEX idx_user_created ON ai_usage (user_id, created_at)');
        $this->addSql('CREATE INDEX idx_operation ON ai_usage (operation)');
        $this->addSql('COMMENT ON COLUMN ai_usage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ai_usage ADD CONSTRAINT FK_8C5C6535A76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ai_usage ADD CONSTRAINT FK_8C5C6535126F525E FOREIGN KEY (item_id) REFERENCES "items" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ai_usage DROP CONSTRAINT FK_8C5C6535A76ED395');
        $this->addSql('ALTER TABLE ai_usage DROP CONSTRAINT FK_8C5C6535126F525E');
        $this->addSql('DROP TABLE ai_usage');
    }
}
