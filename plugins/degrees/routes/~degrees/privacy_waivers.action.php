<h1>Privacy waivers</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;

// form to add a new preferred name
echo "<h2>Add privacy waiver</h2>";
$form = new FormWrapper();
$netid = (new Field('NetID', new NetIDInput))
    ->setRequired(true)
    ->addForm($form);
$name = (new Field('name'))
    ->setRequired(true)
    ->addForm($form);
if ($form->ready()) {
    // check if it already exists
    $check = DB::query()->from('privacy_waiver')->where('netid = ?', [$netid->value()])->count();
    if ($check) {
        Notifications::flashWarning('A privacy waiver already exists for ' . $netid->value());
        throw new RefreshException();
    }
    // insert new record
    DB::query()->insertInto('privacy_waiver', [
        'netid' => $netid->value(),
        'name' => $name->value(),
        'created' => time(),
        'created_by' => Session::uuid()
    ])->execute();
    Notifications::flashConfirmation('Privacy waiver saved for ' . $netid->value() . '<br>They can now be imported into the degree list.');
    // refresh
    throw new RefreshException();
}
echo $form;

// display existing records
echo "<h2>Privacy waivers</h2>";
$waivers = DB::query()->from('privacy_waiver')
    ->order('id desc');
$table = new PaginatedTable(
    $waivers,
    function (array $row): array {
        return [
            $row['netid'],
            $row['name'],
            Format::date($row['created']) . ' by ' . Users::user($row['created_by']),
            (new CallbackLink(function () use ($row) {
                DB::query()
                    ->delete('privacy_waiver', $row['id'])
                    ->execute();
            }))
                ->setData('target', '_frame')
                ->addChild('delete')
        ];
    },
    [
        new ColumnSortingHeader('NetID', 'netid', $names),
        new ColumnSortingHeader('Name', 'name', $names),
        'Created',
        '',
    ]
);
echo $table;
