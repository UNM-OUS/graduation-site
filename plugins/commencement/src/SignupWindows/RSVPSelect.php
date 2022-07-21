<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DateTime;
use DigraphCMS\Content\Pages;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\Users\Users;

/**
 * @method RSVP|null fetch()
 * @method RSVP[] fetchAll()
 */
class RSVPSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return new RSVP(
            $row['for'],
            Pages::get($row['window']),
            json_decode($row['data'], true),
            Users::get($row['created_by']),
            (new DateTime())->setTimestamp($row['created']),
            Users::get($row['updated_by']),
            (new DateTime())->setTimestamp($row['updated'])
        );
    }
}
