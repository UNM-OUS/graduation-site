<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;
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
    Many schools, colleges, departments, and programs also hold their own <a href="<?php echo new URL('/convocations/'); ?>">departmental convocations</a>.
</p>

<?php

$semester = Semesters::current();
echo "<h2>$semester</h2>";

// if ($semester->semester() == 'Summer') {
//     Notifications::printNotice(
//         sprintf(
//             'Commencements are only held at the end of the Spring and Fall semesters, please check back here after %s for more information about the %s Commencement.',
//             Format::date($semester->nextFull()->start(), true),
//             $semester->nextFull()
//         )
//     );
// }
