<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;

/** @var RSVP */
$rsvp = Context::fields()['rsvp'];
$window = $rsvp->window();
$commencement = $window->commencement();

?>
<h2>Faculty instructions</h2>
<p>
    The ceremony <em>begins</em> <?php echo Format::date($commencement->time(), true, true); ?> promptly at <?php echo Format::time($commencement->time(), true, true); ?>.
    You will need to arrive earlier than that to provide time to park, check in, and be situated for your role.
    You will get more precise instructions closer to the date of the ceremony, which will be tailored to your role in the ceremony.
</p>
