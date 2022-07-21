<h1>Preferred name overrides</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

// form to add a new preferred name
echo "<h2>Add preferred name</h2>";
$form = new FormWrapper();
$netid = (new Field('NetID', new NetIDInput))
    ->setRequired(true)
    ->addForm($form);
$firstName = (new Field('First name'))
    ->addTip('Leave blank to use name as imported')
    ->addForm($form);
$lastName = (new Field('Last name'))
    ->addTip('Leave blank to use name as imported')
    ->addForm($form);
if ($form->ready()) {
    // insert/update degree_preferred_name record
    if (DB::query()->from('degree_preferred_name')->where('netid = ?', [$netid->value()])->count()) {
        // update existing record
        DB::query()->update('degree_preferred_name', [
            'first_name' => $firstName->value() ? $firstName->value() : null,
            'last_name' => $lastName->value() ? $lastName->value() : null,
        ])
            ->where('netid = ?', [$netid->value()])
            ->execute();
        Notifications::flashConfirmation('Updated preferred name for ' . $netid->value());
    } else {
        // update existing record
        DB::query()->insertInto('degree_preferred_name', [
            'netid' => $netid->value(),
            'first_name' => $firstName->value() ? $firstName->value() : null,
            'last_name' => $lastName->value() ? $lastName->value() : null,
        ])->execute();
        Notifications::flashConfirmation('Inserted new preferred name for ' . $netid->value());
    }
    // save into personinfo
    PersonInfo::setFor($netid->value(), [
        'firstname' => $firstName->value(),
        'lastname' => $lastName->value(),
        'fullname' => ($firstName->value() && $lastName->value()) ? $firstName->value() . ' ' . $lastName->value() : ''
    ]);
    // update all records in degrees
    $update = array_filter([
        'firstname' => $firstName->value(),
        'lastname' => $lastName->value()
    ], function ($e) {
        return !!$e;
    });
    if ($update) {
        $updated = DB::query()->update('degree', $update)
            ->where('netid = ?', [$netid->value()])
            ->execute();
        Notifications::flashConfirmation("Updated $updated existing degree records");
    }
    // refresh
    throw new RefreshException();
}
echo $form;

// display existing records
echo "<h2>Preferred names</h2>";
$names = DB::query()->from('degree_preferred_name')
    ->order('id desc');
$table = new PaginatedTable(
    $names,
    function (array $row): array {
        return [
            $row['netid'],
            $row['first_name'],
            $row['last_name'],
            (new CallbackLink(function () use ($row) {
                DB::query()
                    ->delete('degree_preferred_name', $row['id'])
                    ->execute();
            }))
                ->setData('target', '_frame')
                ->addChild('delete')
        ];
    },
    [
        new ColumnSortingHeader('NetID', 'netid', $names),
        new ColumnSortingHeader('First name', 'first_name', $names),
        new ColumnSortingHeader('Last name', 'last_name', $names),
        '',
    ]
);
echo $table;
