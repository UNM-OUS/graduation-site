<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CommencementSignupTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('commencement_signup')
            ->addColumn('uuid', 'uuid')
            ->addIndex('uuid', ['unique' => true])

            ->addColumn('for', 'string')
            ->addIndex('for')

            ->addColumn('window', 'uuid')
            ->addIndex('window')

            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid')
            ->addForeignKey(['created_by'], 'user', ['uuid'])

            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid')
            ->addForeignKey(['updated_by'], 'user', ['uuid'])

            ->addColumn('data', 'json')

            ->create();
    }
}
