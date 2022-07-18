<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PrivacyWaiverTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('privacy_waiver')
            ->addColumn('netid', 'string', ['length' => 20])
            ->addColumn('name', 'string')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid')
            ->addIndex('netid')
            ->addIndex('name')
            ->addIndex('created')
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->create();
    }
}
