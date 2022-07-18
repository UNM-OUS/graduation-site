<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NameOverrideTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('degree_preferred_name')
            ->addColumn('netid', 'string', ['length' => 20])
            ->addIndex('netid', ['unique' => true])
            ->addColumn('first_name', 'string', ['null' => true])
            ->addIndex('first_name')
            ->addColumn('last_name', 'string', ['null' => true])
            ->addIndex('last_name')
            ->create();
    }
}
