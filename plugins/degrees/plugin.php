<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;

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
}
