<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @method Degree|null fetch()
 * @method Degree[] fetchAll()
 */
class DegreeSelect extends AbstractMappedSelect
{
    /**
     * Add a clause to omit override degrees from this query, such as for the program.
     *
     * @return $this
     */
    function noOverrides()
    {
        $this->where('override <> 1');
        return $this;
    }

    function doRowToObject(array $row)
    {
        return Degree::fromDatabaseRow($row);
    }
}
