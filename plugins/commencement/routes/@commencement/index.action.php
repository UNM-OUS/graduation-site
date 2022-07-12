<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

Context::response()->enableCache();

/** @var CommencementEvent */
$event = Context::page();

echo "<h1>" . $event->name() . "</h1>";
