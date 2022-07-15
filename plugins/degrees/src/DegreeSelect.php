<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @method Degree|null fetch()
 * @method Degree[] fetchAll()
 */
class DegreeSelect extends AbstractMappedSelect
{
    function doRowToObject(array $row)
    {
        return Degree::fromDatabaseRow($row);
    }
}