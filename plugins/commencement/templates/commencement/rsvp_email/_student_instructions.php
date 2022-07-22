<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;

/** @var RSVP */
$rsvp = Context::fields()['rsvp'];
$window = $rsvp->window();
$commencement = $window->commencement();

?>
<h2>Student/guest instructions</h2>
<p>
    The ceremony <em>begins</em> <?php echo Format::date($commencement->time(), true, true); ?> promptly at <?php echo Format::time($commencement->time(), true, true); ?>.
    You should arrive by <?php echo Format::time($commencement->time()->modify('-1.5 hours'), true, true); ?> to provide time to park, check in, and be seated.
    More information about what you and your guests can expect the day of the ceremony and what you need to do to prepare is available online:
</p>

<ul>
    <li><?php echo (new URL('/students/'))->html(); ?></li>
    <li><?php echo (new URL('/guests/'))->html(); ?></li>
    <li><?php echo (new URL('/parking/'))->html(); ?></li>
</ul>