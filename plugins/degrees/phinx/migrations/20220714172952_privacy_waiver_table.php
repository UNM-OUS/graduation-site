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
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid')
            ->addIndex('netid')
            ->addIndex('name')
            ->addIndex('created')
            ->addIndex('updated')
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->create();
    }
}
