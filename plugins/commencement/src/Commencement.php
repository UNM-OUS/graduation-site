<?php

namespace DigraphCMS_Plugins\unmous\commencement;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class Commencement
{

    public function get(?string $uuid): ?CommencementEvent
    {
        if (!$uuid) return null;
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
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
}
