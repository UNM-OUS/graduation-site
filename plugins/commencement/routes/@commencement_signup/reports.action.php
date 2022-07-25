<h1>Commencement reports</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Sidebar\Sidebar;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVPs;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

/** @var SignupWindow */
$window = Context::page();

Sidebar::addTop(function () use ($window) {
    $form = new FormWrapper();
    $form->button()->setText('Update filters');

    $cancelled = (new CheckboxField('Cancelled RSVPs'))
        ->setDefault(!!Context::arg('cancelled'))
        ->addForm($form);

    $accommodations = (new CheckboxField('Only RSVPs with accommodations requests'))
        ->setDefault(!!Context::arg('accommodations'))
        ->addForm($form);

    if ($window->isForFaculty()) {
        $regalia = (new CheckboxField('Only RSVPs with regalia orders'))
            ->setDefault(!!Context::arg('regalia'))
            ->addForm($form);
        $role = (new Field('Role at Commencement', new SELECT(array_merge(
            ['any' => 'Any role'],
            Config::get('commencement.faculty_roles')
        ))))
            ->setDefault(Context::arg('role'))
            ->setRequired(true)
            ->addForm($form);
    }

    if ($form->ready()) {
        $url = Context::url();
        $cancelled->value() ? $url->arg('cancelled', 1) : $url->unsetArg('cancelled');
        $accommodations->value() ? $url->arg('accommodations', 1) : $url->unsetArg('accommodations');
        (isset($regalia) && $regalia->value()) ? $url->arg('regalia', 1) : $url->unsetArg('regalia');
        (isset($role) && $role->value() != 'any') ? $url->arg('role', $role->value()) : $url->unsetArg('role');
        throw new RedirectException($url);
    }

    return '<h2>Report settings</h2>' . $form;
});

$rsvps = RSVPs::select($window);
if (Context::arg('cancelled')) $rsvps->where('${data.cancelled} = 1');
else $rsvps->where('(${data.cancelled} <> 1 OR ${data.cancelled} is null)');
if (Context::arg('accommodations')) $rsvps->where('${data.accommodations.requested}');
if (Context::arg('regalia')) $rsvps->where('${data.regalia} = 1');
if (Context::arg('role')) $rsvps->where('${data.role} = ?', [Context::arg('role')]);

// set up actual table
$table = new PaginatedTable(
    $rsvps,
    [$window, 'rsvpReportCells'],
    $window->rsvpReportHeaders()
);

// set up download options
$filename = [$window->name()];
if (Context::arg('cancelled')) $filename[] = 'Cancelled';
if (Context::arg('accommodations')) $filename[] = 'Accommodations';
if (Context::arg('regalia')) $filename[] = 'Regalia';
if (Context::arg('role')) $filename[] = Context::arg('role');
$filename[] = date('Y-m-d');
$table->download(
    implode(' - ', $filename),
    [$window, 'rsvpReportDownloadCells'],
    $window->rsvpReportDownloadHeaders()
);

// print table
echo $table;
