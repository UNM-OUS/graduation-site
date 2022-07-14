<h1>Future Commencements</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\Commencement;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

Context::response()->enableCache();

Notifications::printNotice('Future Commencement information is as accurate as we can make it based on future semester dates and known planning considerations, but may be subject to changes. Check back on this site after the start of the semester in question for finalized dates, times, and locations.');

$query = Commencement::future();
$table = new PaginatedTable(
    $query,
    function (CommencementEvent $event) {
        return [
            $event->name(),
            Format::datetime($event->time()),
            $event->location()
        ];
    }
);
echo $table;
