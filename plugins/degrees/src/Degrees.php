<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\DB\DB;

class Degrees
{
    public static function select(): DegreeSelect
    {
        return new DegreeSelect(
            DB::query()->from('degree')
                // sort first by semester, then by name
                ->order('degree.semester DESC, degree.lastname ASC, degree.firstname ASC')
        );
    }

    public static function selectAny(): DegreeSelect
    {
        return new DegreeSelect(
            DB::query()->from('degree')
                // sort first by semester, then by name
                ->order('degree.semester DESC, degree.lastname ASC, degree.firstname ASC')
        );
    }
}
