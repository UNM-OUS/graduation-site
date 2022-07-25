<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVPs;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

// Check signup existence and permissions
$rsvp = RSVPs::get(Context::url()->actionSuffix(), Context::page());
if (!$rsvp) throw new HttpError(404);
if (!Permissions::inMetaGroups(['commencement__edit', 'commencement__view']) && !in_array($rsvp->for(), OUS::userNetIDs())) {
    throw new AccessDeniedError('Not allowed to view this RSVP');
}

// title and breadcrumb
Breadcrumb::setTopName($rsvp->name());
printf("<h1>RSVP: %s</h1>", $rsvp->name());

// editing and cancellation tools
echo "<div class='navigation-frame navigation-frame--stateless' id='edit-or-cancel-interface' data-target='_top'>";
if ($rsvp->cancelled()) Notifications::printWarning('This RSVP is marked as cancelled');
// note that we use window()->ended() and not window()->closed(), this way early manual signups can self-serviced edit even if the window isn't open yet for everybody else
if (!$rsvp->window()->ended() || Permissions::inMetaGroup('commencement__edit')) {
    // cancel or uncancel link
    echo (new CallbackLink(function () use ($rsvp) {
        $rsvp->setCancelled(!$rsvp->cancelled())
            ->save();
        // send status update email
        if ($rsvp->cancelled()) $rsvp->sendNotificationEmail('cancelled');
        else $rsvp->sendNotificationEmail('uncancelled');
    }))->addChild($rsvp->cancelled() ? "Un-cancel this RSVP" : "Cancel this RSVP")
        ->setData('target', '_frame');
    // separator
    echo ' | ';
    // edit link
    echo $rsvp->window()->url('_form', ['for' => $rsvp->for()])
        ->setName('Edit this RSVP')
        ->html();
}
echo "</div>";

// display RSVP
$data = [
    ['Commencement event', $rsvp->window()->commencement()->url()->html()],
    ['Signup window', $rsvp->window()->url()->html()],
    ['Name', $rsvp->name()],
    ['Name pronunciation', $rsvp->pronunciation()],
    ['Email', $rsvp->email()],
    ['Role at Commencement', Config::get('commencement.faculty_roles.' . $rsvp['role']) ?? $rsvp['role']],
    ['Regalia', $rsvp['regalia'] ? 'Regalia rental requested' : null],
    ['Hooder', $rsvp->hooder()],
    ['Degree', $rsvp['degree'] ? implode(', ', [$rsvp['degree']['college'], $rsvp['degree']['program']]) : ''],
];

if ($rsvp['accommodations']) $data[] = ['Accommodations', [
    'Phone' => $rsvp['accommodations.phone'],
    'Requested' => implode(', ', array_diff($rsvp['accommodations.needs'], ['other'])),
    'Other accommodations' => $rsvp['accommodations.extra'] ? '<br>' . $rsvp['accommodations.extra'] : null
]];

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
