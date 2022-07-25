<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\Content\PageSelect;
use Envms\FluentPDO\Queries\Select;

/**
 * @method SignupWindow|null fetch()
 * @method SignupWindow[] fetchAll()
 */
class SignupWindowSelect extends PageSelect
{
    public function __construct(Select $query)
    {
        parent::__construct($query);
        $this
            ->order('(CASE WHEN ${data.start} <= ' . time() . ' AND ${data.end} > ' . time() . ' THEN 1 ELSE 2 END)')
            ->order('(CASE WHEN ${data.start} > ' . time() . ' THEN 1 ELSE 2 END)')
            ->order('(CASE WHEN ${data.type} = "undergrad" THEN 1 WHEN ${data.type} = "master" THEN 2 WHEN ${data.type} = "terminal" THEN 3 ELSE 10 END)')
            ->order('${data.start} ASC')
            ->order('${data.end} ASC');
    }
}
