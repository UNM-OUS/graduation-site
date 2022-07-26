<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;
use DigraphCMS_Plugins\unmous\degrees\Degree;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

/** @var CommencementEvent */
$event = Context::page();

$constraint = DegreeSemesterConstraint::forCommencementProgram($event->semester());
$degrees = $constraint->degrees()->where('netid', OUS::userNetIDs());

?>
<h1>How will my name appear in the program?</h1>
<p>
    Only degrees listed in Banner as <em><?php echo $constraint; ?></em> will be listed in the program.
    We updated the program degree list multiple times per week, so as long as your degree is updated in Banner by <?php echo Format::date($event->relativeTime('-2 days'), true); ?>
    it should appear in the program the day of the ceremony.
    If your degree is entered into Banner late, it should still appear in the program eventually, as the database will be updated even after the day of the event.
    Questions about your degree status should be directed first to your academic advisor, and then the Office of the Registrar. 
    There is a time lag in the process, and even once your Banner records are updated, it may take as long as a week for the change to propagate through various reporting tools and reach this site.
</p>
<p>
    Please note that the honors displayed here are only <em>University Honors</em>, which may have stricter requirements than departmental designations of <em>Summa cum laude</em>, <em>Magna cum laude</em>, etc.
    Departmental honors do not appear in this program.
    Prior to the finalization of your records University Honors may also not appear if you will not meet all of the requirements until the end of your final semester.
    University Honors displayed here are also only a projection, and your final honors are contingent on your final grades and total credit hours.
    Your honors status here should not be considered an official designation of your final honors status.
</p>

<h2>Degree records currently eligible to appear in the <?php echo $event->name(); ?> program</h2>
<?php

$table = new PaginatedTable(
    $degrees,
    function (Degree $degree): array {
        return [
            $degree->status(),
            $degree->semester(),
            $degree->firstName(),
            $degree->lastName(),
            $degree->college(),
            $degree->department(),
            $degree->program(),
            $degree->major1(),
            $degree->major2(),
            $degree->honors(),
        ];
    },
    [
        'Status',
        'Semester',
        'First name',
        'Last name',
        'School/College',
        'Department',
        'Program',
        'Major 1',
        'Major 2',
        'University honors'
    ]
);
echo $table;
