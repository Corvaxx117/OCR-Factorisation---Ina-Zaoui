<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le proprietaire d un media devient obligatoire : l auteur est toujours l utilisateur connecte.
 */
final class Version20260927110301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend le proprietaire d un media obligatoire et fixe la valeur par defaut de user.active';
    }

    public function up(Schema $schema): void
    {
        // Les medias sans proprietaire sont rattaches a l'administrateur avant la pose de la contrainte.
        $this->addSql('UPDATE media SET user_id = (SELECT id FROM "user" WHERE admin = true ORDER BY id LIMIT 1) WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE media ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER active SET DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media ALTER user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER active DROP DEFAULT');
    }
}
