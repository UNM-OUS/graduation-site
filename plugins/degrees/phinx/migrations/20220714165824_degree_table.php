<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DegreeTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('degree')
            ->addColumn('override', 'boolean')
            ->addIndex('override')

            ->addColumn('userid', 'string', ['length' => 32])
            ->addIndex('userid')

            ->addColumn('netid', 'string', ['length' => 20, 'null' => true])
            ->addIndex('netid')

            ->addColumn('firstname', 'string')
            ->addIndex('firstname')
            ->addColumn('lastname', 'string')
            ->addIndex('lastname')

            ->addColumn('gradstatus', 'string')
            ->addIndex('gradstatus')

            ->addColumn('semester', 'integer')
            ->addIndex('semester')

            ->addColumn('honors', 'string', ['null' => true])
            ->addIndex('honors')

            ->addColumn('level', 'string')
            ->addIndex('level')

            ->addColumn('college', 'string')
            ->addColumn('department', 'string', ['null' => true])
            ->addColumn('program', 'string')
            ->addColumn('major1', 'string')
            ->addColumn('major2', 'string', ['null' => true])
            ->addColumn('minor1', 'string', ['null' => true])
            ->addColumn('minor2', 'string', ['null' => true])
            ->addColumn('dissertation', 'string', ['null' => true])

            ->addColumn('job', 'uuid', ['null' => true])
            ->addIndex('job')

            ->create();
    }
}
