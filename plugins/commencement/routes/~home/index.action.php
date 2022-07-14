<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\commencement\Commencement;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

Context::response()->setSearchIndex(!Context::url()->query());
Context::response()->enableCache();

?>
<h1>University Commencement</h1>

<p>
    Commencement is a tradition that marks the intersection of the past and the future.
    At The University of New Mexico, we thrive at this intersection, embracing our state’s vibrant culture founded on wisdom that has been handed down from our elders—part of a colorful legacy inherited from all those who have shaped the past, present, and future of this rugged, beautiful land.
    At the same time, we look to those same traditions that keep us connected to the lessons of the past as new inspiration for the forging of our future.
</p>

<p>
    The main UNM Commencement ceremony is held at the end of the Spring and Fall semesters, and is very large event.
    UNM graduates from all levels of every school and college are invited to attend.
    Many schools, colleges, departments, and programs also hold their own smaller <a href="<?php echo new URL('/convocations/'); ?>">departmental convocations</a>.
</p>

<?php

// TODO: switch this to current() when everything is ready
$semester = Semesters::currentFull();
$events = Commencement::semester($semester);
if ($semester->semester() == 'Summer' && !$events->count()) {
    Notifications::printNotice(
        sprintf(
            'Commencements are only held at the end of the Spring and Fall semesters, please check back here after %s for more information about the %s Commencement.'
                . ' <a href="%s">Future Commencement dates</a> lists tentative event dates that are currently planned.',
            Format::date($semester->nextFull()->start(), true),
            $semester->nextFull(),
            new URL('/future_commencements/')
        )
    );
}

if ($semester->semester() != 'Summer' && !$events->count()) {
    Notifications::printNotice("No events found for $semester, please check back later.");
}

while ($event = $events->fetch()) {
    echo "<div class='card card--light' style='margin-top:2rem;'>";
    printf(
        '<h2><a href="%s">%s</a><small> - %s, %s</small></h2>',
        $event->url(),
        $event->name(),
        Format::datetime($event->time()),
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
                '<li><strong>%s</strong><small> opens %s</small></li>',
                $window->name(),
                Format::datetime($window->start())
            );
        } else {
            printf(
                '<li><strong>%s</strong><small> closed %s</small></li>',
                $window->name(),
                Format::datetime($window->end())
            );
        }
    }
    echo "</ul>";
    echo $event->richContent('body');
    echo "</div>";
}

echo "<p style='margin-top:4rem;'>";
echo implode(
    ' | ',
    [
        (new URL('/future_commencements/'))->html(),
        (new URL('/past_commencements/'))->html()
    ]
);
echo "</p>";
