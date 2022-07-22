<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Templates;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;

/** @var RSVP */
$rsvp = Context::fields()['rsvp'];
$window = $rsvp->window();
$commencement = $window->commencement();

?>
<h1>RSVP confirmation for <?php echo $commencement->name(); ?></h1>

<p>
    <a href="<?php echo $rsvp->url(); ?>">Your RSVP for <?php echo $rsvp->name(); ?></a>, for <?php echo $commencement->url()->html(); ?> <?php echo $window->url()->html(); ?> is confirmed.
    You may freely edit or cancel your RSVP until the signup window closes <?php echo Format::datetime($window->end(), true, true); ?>.
    The ceremony is scheduled for <?php echo Format::datetime($commencement->time(), true, true); ?>.
</p>

<?php

// type-specific instructions
if ($window->isForStudents()) echo Templates::render('commcencement/rsvp_email/_student_instructions.php');
elseif ($window->isForFaculty()) echo Templates::render('commcencement/rsvp_email/_faculty_instructions.php');
else echo Templates::render('commcencement/rsvp_email/_fallback_instructions.php');
?>

<?php if ($rsvp['regalia']) : ?>

    <h2>Your regalia rental</h2>

    <p>
        Regalia rental orders are placed with the bookstore several weeks before the event.
        If you are ordering past the regalia deadline listed on <a href="<?php echo $window->url(); ?>">signup window page</a> then your order is not guaranteed.
    </p>

    <p>
        If you are a faculty honor processional member or hooder you will pick up your own regalia at the bookstore the week of Commencement, and return your own regalia to the bookstore afterwards.
        You will be notified in a separate email when it is ready to pick up.
    </p>

<?php endif; ?>