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
    /**
     * Construct using a FPDO query and the class of the DataObjectSource that
     * spawned this query.
     *
     * @param Select $query
     */
    public function __construct(Select $query)
    {
        parent::__construct($query);
        $this->order('(CASE WHEN ${data.start} <= ? AND ${data.end} > ? THEN 1 ELSE 2 END)', [time(), time()])
            ->order('(CASE WHEN ${data.start} > ? THEN 1 ELSE 2 END)', [time()])
            ->order('(CASE WHEN ${data.type} = "undergrad" THEN 1 WHEN ${data.type} = "masters" THEN 2 WHEN ${data.type} = "terminal" THEN 3 ELSE 10 END)')
            ->order('${data.start} ASC')
            ->order('${data.end} ASC');
    }
}
