<h1>Search by NetID</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;

$form = new FormWrapper();
$form->addClass('inline-form');
$form->button()->setText('Search');

$query = (new NetIDInput())
    ->setDefault(Context::arg('netid'))
    ->setRequired(true);
$form->addChild($query);

if ($form->ready()) throw new RedirectException(new URL('search_by_netid.html?netid=' . $query->value()));

echo $form;

// display results
if (!($netid = Context::arg('netid'))) return;

// degree records
echo "<h2>Degree records</h2>";
echo new DegreeTable(Degrees::select()
    ->where('netid = ?', [$netid]));

// preferred names
echo "<h2>Preferred names</h2>";
$names = DB::query()->from('degree_preferred_name')
    ->where('netid = ?', [Context::arg('netid')])
    ->order('id desc');
$table = new PaginatedTable(
    $names,
    function (array $row): array {
        return [
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
        'First name',
        'Last name',
        '',
    ]
);
echo $table;

echo "<h2>Privacy waivers</h2>";
$waivers = DB::query()->from('privacy_waiver')
    ->where('netid = ?', [Context::arg('netid')])
    ->order('id desc');
$table = new PaginatedTable(
    $waivers,
    function (array $row): array {
        return [
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
        'Name',
        'Created',
        '',
    ]
);
echo $table;
