<?php

namespace DigraphCMS_Plugins\unmous\commencement;

use DigraphCMS\DB\DB;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class Commencement extends AbstractPlugin
{
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
}
