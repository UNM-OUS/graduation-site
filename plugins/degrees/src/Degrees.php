<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\DB\DB;

class Degrees
{
    public static function select(): DegreeSelect
    {
        return new DegreeSelect(
            DB::query()->from('degree')
                // join on privacy waivers
                ->leftJoin('privacy_waiver on degree.netid = privacy_waiver.netid')
                // only allow degrees with either no privacy flag or a privacy waiver
                ->where('(degree.privacy <> 1 OR privacy_waiver.id)')
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

    public static function privacyFlagged(): DegreeSelect
    {
        return new DegreeSelect(
            DB::query()->from('degree')
                // join on privacy waivers
                ->leftJoin('privacy_waiver on degree.netid = privacy_waiver.netid')
                // only allow degrees with either no privacy flag or a privacy waiver
                ->where('(degree.privacy = 1)')
                // sort first by semester, then by name
                ->order('degree.semester DESC, degree.lastname ASC, degree.firstname ASC')
        );
    }
}
