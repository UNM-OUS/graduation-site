<h1>Past Commencements</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\Commencement;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

Context::response()->enableCache();

$query = Commencement::past();
$table = new PaginatedTable(
    $query,
    function (CommencementEvent $event) {
        return [
            $event->url()->html(),
            Format::datetime($event->time()),
            $event->location()
        ];
    }
);
echo $table;
