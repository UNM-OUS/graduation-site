<?php

namespace DigraphCMS_Plugins\unmous\commencement;

use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class CommencementPlugin extends AbstractPlugin
{

    public static function onShortCode_commtime(ShortcodeInterface $s): ?string
    {
        return static::date_and_time_tag($s, 'time');
    }

    public static function onShortCode_commdate(ShortcodeInterface $s): ?string
    {
        return static::date_and_time_tag($s, 'date');
    }

    public static function onShortCode_commdatetime(ShortcodeInterface $s): ?string
    {
        return static::date_and_time_tag($s, 'datetime');
    }

    public function onStaticUrlPermissions_commencement(URL $url, User $user): ?bool
    {
        return Permissions::inMetaGroup('commencement__edit', $user);
    }

    public function onUserMenu_user(UserMenu $menu)
    {
        $menu->addURL(new URL('/~commencement/'));
    }

    protected static function date_and_time_tag(ShortcodeInterface $s, string $format_method): ?string
    {
        $event = $s->getBbCode();
        switch ($event) {
            case 'graduate':
                $event = Commencement::latest('graduate')->fetch();
                break;
            case 'undergraduate':
                $event = Commencement::latest('undergraduate')->fetch();
                break;
            default:
                $event = Commencement::get($event)
                    ?? Commencement::latest()->fetch();
                break;
        }
        if (!$event) return null;
        if ($s->getParameter('offset')) $time = $event->relativeTime($s->getParameter('offset'));
        else $time = $event->time();
        return Format::$format_method($time);
    }
}
