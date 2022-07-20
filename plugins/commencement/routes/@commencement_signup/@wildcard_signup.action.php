<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\Signups;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

// Check signup existence and permissions
$rsvp = Signups::get(Context::url()->actionSuffix(), Context::page());
if (!$rsvp) throw new HttpError(404);
if (!Permissions::inMetaGroups(['commencement__edit', 'commencement__view']) && !in_array($signup['for'], OUS::userNetIDs())) {
    throw new AccessDeniedError('Not allowed to view this RSVP');
}

// display RSVP
Breadcrumb::setTopName($rsvp->name());
printf("<h1>RSVP: %s</h1>", $rsvp->name());

$data = [
    ['Name', $rsvp->name()],
    ['Name pronunciation', $rsvp->pronunciation()],
    ['Email', $rsvp->email()],
    ['Role at Commencement', $rsvp['role']],
    ['Regalia', $rsvp['regalia'] ? 'Regalia rental requested' : null],
    ['Hooder', $rsvp->hooder()],
    ['Degree', implode(', ', [$rsvp['degree']['college'], $rsvp['degree']['program']])],
    ['Accommodations', $rsvp['accommodations']],
];

$data[] = ['Created', Format::date($rsvp->created()) . ' by ' . $rsvp->createdBy()];
$data[] = ['Modified', Format::date($rsvp->updated()) . ' by ' . $rsvp->updatedBy()];

$data = array_filter($data, function ($e) {
    return !!$e[1];
});

$table = new PaginatedTable($data, function ($e) {
    return $e;
});
$table->paginator()->perPage(100);
echo $table;
