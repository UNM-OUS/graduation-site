<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\DB\DB;

class Signups
{
    public static function get(?string $uuid, SignupWindow $window = null): ?Signup
    {
        if (!$uuid) return null;
        return static::select($window)
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function select(SignupWindow $window = null): SignupSelect
    {
        $select = new SignupSelect(
            DB::query()->from('commencement_signup')
        );
        if ($window) $select->where('window = ?', [$window->uuid()]);
        return $select;
    }
}
