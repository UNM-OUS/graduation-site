<h1>Event RSVP</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\Signup;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

Permissions::requireGroup('users');

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

$signup = new Signup($for, Context::page());
echo $signup->form();
