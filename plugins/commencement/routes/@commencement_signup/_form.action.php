<h1>Event RSVP</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;
use DigraphCMS_Plugins\unmous\degrees\Degree;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

/** @var SignupWindow */
$window = Context::page();

// figure out who this  signup will be for
$for = Permissions::inMetaGroups(['commencement__edit', 'commencement__signupothers'])
    ? Context::arg('for')
    : null;
if (!$for) {
    $netIDs = OUS::userNetIDs(Session::uuid());
    if (count($netIDs) != 1) {
        if (!in_array(Context::arg('for'), $netIDs)) throw new RedirectException(new URL('./'));
        else $for = Context::arg('for');
    } else $for = $netIDs[0];
}
$for = htmlspecialchars($for);

// basic permissions, is a user, window is open or user has special permissions
Permissions::requireGroup('users');
$rsvp = RSVP::getFor($for, $window);
if (!($window->open() || (!$window->ended() && $rsvp->exists()) || Permissions::inMetaGroup('commencement__edit'))) {
    throw new AccessDeniedError("This signup window is closed");
}

// special degree-checking for student signups
if (in_array($window->type(), Config::get('commencement.student_signup_types'))) {
    $eligible = DegreeSemesterConstraint::forCommencement($window->commencement()->semester())->degrees();
    if ($levels = $window->degreeLevels()) $eligible->where('level', $levels);
    $eligible->where('netid', $for);
    if (!$eligible->count()) {
        // notes that may help user, error message and any degrees that were found
        Notifications::printError("No eligible degree records found for <code>$for</code>");
        $all = Degrees::select()->where('netid = ?', [$for]);
        if (!$all->count()) {
            echo "<p>No degree records were found in our system.</p>";
        } else {
            echo "<p>The following degree records were found for you, but they are not currently eligible to sign up using this signup form:</p>";
            echo new PaginatedTable(
                $all,
                function (Degree $degree) {
                    return [
                        $degree->status(),
                        $degree->semester(),
                        $degree->college(),
                        $degree->department(),
                        $degree->program()
                    ];
                },
                ['Status', 'Semester', 'School/College', 'Department', 'Program']
            );
        }
        // general eligibility template
        echo Templates::render('commencement/student_eligibility.php', ['semester' => $window->commencement()->semester()]);
        return;
    }
}

// get and print form
$form = $rsvp->form();
$form->addCallback(function () use ($rsvp) {
    throw new RedirectException($rsvp->url());
});

echo $form;
