<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\EmailOrNetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

/** @var SignupWindow */
$window = Context::page();

printf('<h1>%s</h1>', $window->name());

if (Permissions::inMetaGroups(['commencement__edit', 'commencement__signupothers'])) {
    // signup interface for fancy people
    $form = new FormWrapper();
    $form->button()->setText('Begin RSVP');
    if (in_array($window->type(), Config::get('commencement.student_signup_types'))) {
        // use NetID field for student signups
        $for = (new Field('NetID', new NetIDInput))
            ->setRequired(true)
            ->addForm($form);
    } else {
        // otherwise use a netid or email field
        $for = (new Field('NetID or email', new EmailOrNetIDInput))
            ->addTip('<strong>If at all possible</strong> sign users up using their main campus NetID. Users RSVPed using an email address here will not be able to access any self-service tools for updating or cancelling their RSVP.')
            ->addTip('A field is provided on the next page for a contact email address, and non-<em>@unm.edu</em> emails such as <em>@salud.unm.edu</em> can be added there so that RSVP confirmations go to the right address.')
            ->setRequired(true)
            ->addForm($form);
    }
    if ($form->ready()) {
        throw new RedirectException(new URL('_form.html?for=' . $for->value()));
    }
    echo $form;
} elseif ($window->open()) {
    if (Permissions::inGroup('users')) {
        // signup interface for regular users
        $netIDs = OUS::userNetIDs(Session::uuid());
        // list existing RSVPs for these NetIDs
        $rsvps = [];
        // display options to create an RSVP
        if (!$netIDs) Notifications::printError('There are no NetIDs associated with your account');
        elseif (count($netIDs) == 1 && !$rsvps) {
            // display interface for user with one netid
            echo (new A(new URL('_form.html?for=' . $netIDs[0])))->addChild('RSVP for event')->addClass('button');
        } else {
            // display interface for user with multiple netids
        }
    } else {
        Notifications::printNotice(Users::signinUrl()->setName('Log in to RSVP')->html());
    }
}

if ($window->open()) {
    printf(
        '<p><em>Closes %s</small></em></p>',
        Format::datetime($window->end())
    );
} elseif ($window->pending()) {
    printf(
        '<p><em>Opens %s</small></em></p>',
        Format::datetime($window->start())
    );
} else {
    printf(
        '<p><em>Closed %s</small></em></p>',
        Format::datetime($window->end())
    );
}

echo Templates::render('commencement/signup/intro_' . $window->type() . '.php');

if (in_array($window->type(), Config::get('commencement.student_signup_types'))) {
    echo Templates::render('commencement/student_eligibility.php', ['semester' => $window->commencement()->semester()]);
}
