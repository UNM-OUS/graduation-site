<h1>Institutions</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_regalia\Regalia;

$query = Regalia::allInstitutions();
$query->order('regalia_institution.id DESC');

// limit display to particular colors if requested
$colors = explode('|', Context::arg('colors') ?? '');
$colors = array_filter($colors, function ($e) {
    return !!$e;
});
if ($colors) {
    $q = implode(',', array_map(
        function ($e) {
            $e = preg_replace('/[^a-z0-9 \#\%\-]/i', '', $e);
            return "'" . $e . "'";
        },
        $colors
    ));
    $q = "($q)";
    $query->where('institution_color_lining1 IN ' . $q);
    $query->where('institution_color_chevron1 IN ' . $q);
    Notifications::printNotice(sprintf(
        'Currently displaying limited colors<br><a href="%s">change color selections</a> or <a href="%s">clear filter</a>',
        new URL('_color_search.html?default=' . implode('|', $colors)),
        new URL('./')
    ));
} else {
    Notifications::printNotice(sprintf(
        '<a href="%s">Search for institutions that use particular colors</a>',
        new URL('_color_search.html')
    ));
}

// display the table of results
$table = new PaginatedTable(
    $query,
    function (array $row) {
        $id = $row['id'];
        return [
            implode('', [
                (!$row['deprecated']
                    ? new ToolbarLink('Hide', 'hide', function () use ($id) {
                        Regalia::query()
                            ->update('regalia_institution', ['deprecated' => true], $id)
                            ->execute();
                    })
                    : new ToolbarLink('Show', 'show', function () use ($id) {
                        Regalia::query()
                            ->update('regalia_institution', ['deprecated' => false], $id)
                            ->execute();
                    }))
                    ->setAttribute('data-target', '_frame'),
                new ToolbarLink('Copy', 'copy', null, new URL('_add.html?jid=' . $row['jostens_id'])),
                new ToolbarLink('Edit', 'edit', null, new URL('_edit.html?id=' . $row['id']))
            ]),
            $row['label']
                . ($row['deprecated'] ? ' <strong>[HIDDEN]</strong>' : '')
                . ($row['institution_deprecated'] ? ' <strong>[DEPRECATED]</strong>' : ''),
            $row['institution_color_lining1'],
            $row['institution_color_chevron1'],

        ];
    },
    [
        '',
        new ColumnSortingHeader('Label', 'label', $query),
        'Lining',
        'Chevron',
    ]
);

$table->download(
    'Institutions',
    function ($row) {
        return [
            $row['label']
                . ($row['deprecated'] ? ' <strong>[HIDDEN]</strong>' : '')
                . ($row['institution_deprecated'] ? ' <strong>[DEPRECATED]</strong>' : ''),
            $row['institution_color_lining1'],
            $row['institution_color_chevron1'],
            $row['institution_name'],
            $row['institution_city'],
            $row['institution_state'],
        ];
    },
    [
        'Label',
        'Lining',
        'Chevron',
        'Jostens Name',
        'Jostens City',
        'Jostens St',
    ]
);

echo $table;
