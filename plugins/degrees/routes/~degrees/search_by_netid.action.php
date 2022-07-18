<h1>Search by NetID</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
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
echo new DegreeTable(Degrees::selectAny()
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
