<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\UI\Templates;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

/** @var SignupWindow */
$window = Context::page();

printf('<h1>%s</h1>', $window->name());

echo Templates::render('commencement/signup/intro_' . $window->type() . '.php');

if (in_array($window->type(), Config::get('commencement.student_signup_types'))) {
    echo Templates::render('commencement/student_eligibility.php', ['semester' => $window->parentPage()->semester()]);
}
