<?php

namespace DigraphCMS_Plugins\unmous\commencement;

use DigraphCMS\DB\DB;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Commencement extends AbstractPlugin
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

    public function get(?string $uuid): ?CommencementEvent
    {
        if (!$uuid) return null;
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public function onStaticUrlPermissions_commencement(URL $url, User $user): ?bool
    {
        return Permissions::inMetaGroup('commencement__edit', $user);
    }

    public function onUserMenu_user(UserMenu $menu)
    {
        $menu->addURL(new URL('/~commencement/'));
    }

    public static function semester(Semester $semester): CommencementSelect
    {
        return static::select()
            ->where('${data.semester} = ?', [$semester->intVal()]);
    }

    public static function select(): CommencementSelect
    {
        return new CommencementSelect(
            DB::query()
                ->from('page')
                ->where('class = "commencement"')
        );
    }

    public static function latest(string $type = null): CommencementSelect
    {
        $select = static::select()
            ->where('${data.semester} <= ?', [Semesters::current()->intVal()])
            ->order('${data.time} ASC');
        if ($type) $select->where('${data.type} = ?', [$type]);
        return $select;
    }

    public static function current(): CommencementSelect
    {
        return static::select()
            ->where('${data.semester} = ?', [Semesters::current()->intVal()])
            ->order('${data.time} ASC');
    }

    public static function past(): CommencementSelect
    {
        return static::select()
            ->where('${data.semester} < ?', [Semesters::current()->intVal()])
            ->order('${data.time} DESC');
    }

    public static function future(): CommencementSelect
    {
        return static::select()
            ->where('${data.semester} > ?', [Semesters::current()->intVal()])
            ->order('${data.time} ASC');
    }

    protected static function date_and_time_tag(ShortcodeInterface $s, string $format_method): ?string
    {
        $event = $s->getBbCode();
        switch ($event) {
            case 'graduate':
                $event = static::latest('graduate')->fetch();
                break;
            case 'undergraduate':
                $event = static::latest('undergraduate')->fetch();
                break;
            default:
                $event = static::get($event)
                    ?? static::latest()->fetch();
                break;
        }
        if (!$event) return null;
        $time = $event->time();
        try {
            if ($s->getParameter('offset')) $time->modify($s->getParameter('offset'));
        } catch (\Throwable $th) {
            return "{invalid time offset}";
        }
        return Format::$format_method($time);
    }
}
