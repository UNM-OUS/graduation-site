<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;

/** @var RSVP */
$rsvp = Context::fields()['rsvp'];
$window = $rsvp->window();
$commencement = $window->commencement();

?>
<h1>RSVP cancelled for <?php echo $commencement->name(); ?></h1>

<p>
    <a href="<?php echo $rsvp->url(); ?>">Your RSVP for <?php echo $rsvp->name(); ?></a>, for <?php echo $commencement->url()->html(); ?> <?php echo $window->url()->html(); ?> is now cancelled.
    You may continue to freely edit or un-cancel your RSVP until the signup window closes <?php echo Format::datetime($window->end(), true, true); ?>.
    The ceremony is scheduled for <?php echo Format::datetime($commencement->time(), true, true); ?>.
</p>

<?php if ($rsvp['regalia']) : ?>

    <h2>Your regalia rental</h2>

    <p>
        Regalia rentals are generally placed with the bookstore as long as several weeks before the signup deadline, and it may not be possible to cancel them after a certain point.
        If you are a faculty honor processional member or hooder and we are unable to cancel or find another use for your regalia rental order your department <em>may</em> still be charged if you do not attend Commencement.
    </p>

    <p>
        The <a href="<?php echo $window->url(); ?>">signup window page</a> will indicate the regalia deadline once it is known.
        Generally orders cannot be reliably cancelled after that date.
    </p>

<?php endif; ?>