<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\Content\Pages;
use DigraphCMS\DB\DB;

class SignupWindows
{
    public static function get(string $uuid): ?SignupWindow
    {
        return Pages::get($uuid);
    }

    public static function select(): SignupWindowSelect
    {
        return new SignupWindowSelect(
            DB::query()->from('page')
                ->where('class = "commencement_signup"')
        );
    }

    public static function for(string $uuid): SignupWindowSelect
    {
        return new SignupWindowSelect(
            DB::query()->from('page_link')
                ->select('page.*', true)
                ->leftJoin('page ON page.uuid = end_page')
                ->where('class = "commencement_signup"')
                ->where('start_page = ?', [$uuid])
        );
    }
}
