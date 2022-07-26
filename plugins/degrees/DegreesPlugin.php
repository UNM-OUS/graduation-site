<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Session\Authentication;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

class DegreesPlugin extends AbstractPlugin
{
    public function onStaticUrlPermissions_degrees(URL $url, User $user)
    {
        return Permissions::inMetaGroup('degrees__admin', $user);
    }

    public function onUserMenu_user(UserMenu $menu)
    {
        $menu->addURL(new URL('/~degrees/'));
    }

    /**
     * Assign new users from CAS NetIDs a default name based on their degree info if found
     *
     * @param User $user
     * @param string $source
     * @param string $provider
     * @param string $netID
     * @return void
     */
    public static function onCreateUser_cas_netid(User $user, string $source, string $provider, string $netID)
    {
        $degree = Degrees::select()->where('netid', $netID)->fetch();
        if ($degree) {
            $user->name($degree->firstName());
        }
    }

    public static function onAuthentication(Authentication $auth)
    {
        $user = $auth->user();
        if ($user['name_explicitly_set']) return;
        $netIDs = OUS::userNetIDs($user->uuid());
        foreach ($netIDs as $netID) {
            $degree = Degrees::select()->where('netid', $netID)->fetch();
            if ($degree) {
                $user->name($degree->firstName());
                $user['name_explicitly_set'] = true;
                $user->update();
            }
        }
    }
}
