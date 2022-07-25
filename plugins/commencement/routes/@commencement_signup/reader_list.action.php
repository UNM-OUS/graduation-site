<h1>Reader name list</h1>
<p>
    This table is meant to be downloaded and sent to the readers ahead of time so that they can study and practice the names they're going to need to read.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVPs;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

/** @var SignupWindow */
$window = Context::page();

$rsvps = RSVPs::select($window);
$rsvps->where('(${data.cancelled} <> 1 OR ${data.cancelled} is null)');
$rsvps->order('${data.name}');

$table = new PaginatedTable(
    $rsvps,
    function (RSVP $rsvp) {
        return [
            $rsvp->name(),
            $rsvp['pronunciation']
        ];
    },
    [
        'Name', 'Pronunciation'
    ]
);

$table->download(
    'Reader List - ' . $window->name(),
    function (RSVP $rsvp) {
        return [
            $rsvp->name(),
            $rsvp['pronunciation']
        ];
    },
    [
        'Name', 'Pronunciation'
    ]
);

echo $table;
