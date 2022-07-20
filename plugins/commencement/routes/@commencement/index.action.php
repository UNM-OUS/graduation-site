<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

Context::response()->enableCache();

/** @var CommencementEvent */
$event = Context::page();

echo "<h1>" . $event->name() . "</h1>";

printf(
    '<p>%s at %s</p>',
    Format::datetime($event->time(), false, true),
    $event->location()
);

$windows = $event->signupWindows();
echo "<ul>";
while ($window = $windows->fetch()) {
    if ($window->unlisted()) continue;
    if ($window->open()) {
        printf(
            '<li><a href="%s"><strong>%s</strong></a><small> closes %s</small></li>',
            $window->url(),
            $window->name(),
            Format::datetime($window->end())
        );
    } elseif ($window->pending()) {
        printf(
            '<li><a href="%s"><strong>%s</strong></a><small> opens %s</small></li>',
            $window->url(),
            $window->name(),
            Format::datetime($window->start())
        );
    } else {
        printf(
            '<li><a href="%s"><strong>%s</strong></a><small> closed %s</small></li>',
            $window->url(),
            $window->name(),
            Format::datetime($window->end())
        );
    }
}
echo "</ul>";

echo $event->richContent('body');
