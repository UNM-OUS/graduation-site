<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

$semester = Context::fields()['semester'] ?? Semesters::current()->next();
$constraint = DegreeSemesterConstraint::forCommencement($semester);

?>
<div class="card card--light">
    <h1>Student signup eligibility</h1>
    <p>
        To sign up for Commencement, your adviser must have entered a degree record for you in the Banner database, listed as <em><?php echo $constraint; ?></em>.
        Questions about your degree status should be directed first to your academic advisor, and then the Office of the Registrar.
    </p>
    <p>
        There is a time lag in the process, and even once your Banner records are updated, it may take as long as a week for the change to propagate through various reporting tools and reach this site.
        Please be patient and strive to ensure that your graduation status in Banner is updated at least a week before the signup deadline.
    </p>
    <p>
        If you have a <a href="https://unm.custhelp.com/app/answers/detail/a_id/2933/~/what-is-my-privacy%2Fconfidentiality-indicator%3F">Privacy/Confidentiality Indicator</a> on your student records with the Office of the Registrar you will not be able to sign up on this site, and your name will not appear in the Commencement program.
        If you have a Confidentiality Indicator but would like to sign up or have your name appear in the program you must either:
    </p>
    <ul>
        <li>submit a <a href="http://registrar.unm.edu/forms/index.html">Confidentiality Change Form</a> to the Office of the Registrar, which can be completed online and will completely remove your Confidentiality Indicator</li>
        <li>submit a privacy waiver form to the Office of the University Secretary, which must be completed in person but will only allow us to release your name and degree information for the purposes of signing up on this site and placing you in the Commencement program</li>
    </ul>
</div>