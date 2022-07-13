<h1>Commencement admin</h1>
<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\commencement\Commencement;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

// Link to look at prior events
ActionMenu::addContextAction(new URL('/past_commencements/', 'Past Commencements'));

// Admin system for current/upcoming semesters
$semesters = Semesters::latestFull()
    ->previousFull()
    ->allUpcomingFull(10);

foreach ($semesters as $semester) {
    echo "<h2>$semester</h2>";
    $events = Commencement::semester($semester);
    foreach ($events as $event) {
        printf(
            "<h3><strong><a href='%s'>%s</a></strong></h3><p>%s | <a href='%s'>edit</a> | <a href='%s'>add signup window</a></p>",
            $event->url(),
            $event->name(),
            Format::datetime($event->time()),
            $event->url('edit'),
            $event->url('_add_commencement_signup')
        );
        $windows = $event->signupWindows();
        echo "<ul>";
        while ($window = $windows->fetch()) {
            printf(
                '<li><small><a href="%s">%s</a> - %s to %s | <a href="%s">edit</a> | <a href="%s">reports</a></small></li>',
                $window->url(),
                $window->name(),
                Format::date($window->start()),
                Format::date($window->end()),
                $window->url('edit'),
                $window->url('reports')
            );
        }
        echo "</ul>";
    }
    printf(
        '<p><small><a href="%s">add event</a></small></p>',
        new URL('add_commencement.html?semester=' . $semester->intVal())
    );
}