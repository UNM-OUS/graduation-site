<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

/** @var CommencementEvent */
$commencement = Context::fields()['commencement'];
$hooderName = Context::fields()['hooder_name'];
$studentName = Context::fields()['student_name'];
?>
<h1>Commencement hooding request</h1>

<p>
    You have been requested as a faculty hooder for <?php echo htmlspecialchars($studentName); ?> at <?php echo $commencement->url()->html() ?>.
    If you would like to hood this student, please enter your own RSVP for Commencement at <a href="https://graduation.unm.edu/">graduation.unm.edu</a> if you have not already done so.
</p>

<p>
    If you do RSVP to attend Commencement, further instructions will be provided to you as the event approaches.
</p>

<?php if (!preg_match('/unm\.edu$/',$hooderEmail)): ?>

<h2>Not UNM Faculty?</h2>

<p>
    Hooders must have a doctoral or terminal degree and a faculty appointment at a college or university.
    If you meet this requirement, but are not faculty at UNM, you may attend and hood your student.
    You will, however, need to contact the Office of the University Secretary at 505-277-4664 to sign up.
    Non-UNM faculty are also unable to order rental regalia, so you will need to have your own academic regalia to wear.
</p>

<?php endif; ?>