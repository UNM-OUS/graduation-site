<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Pagination\PaginatedList;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVPs;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

$user = Users::get(Context::arg('id')) ?? Users::current();

$netIDs = OUS::userNetIDs($user->uuid());
if (!$netIDs) return;

$rsvps = RSVPs::getForNetIDs($netIDs)->order('created DESC');
if (!$rsvps->count()) return;

echo '<h2>Commencement RSVP history</h2>';
echo new PaginatedList($rsvps, function (RSVP $rsvp) {
    return sprintf(
        '%s &gt; %s &gt; <a href="%s">%s%s</a>',
        $rsvp->window()->commencement()->url()->html(),
        $rsvp->window()->url()->html(),
        $rsvp->url(),
        $rsvp->name(),
        $rsvp->cancelled() ? ' - CANCELLED' : ''
    );
});
