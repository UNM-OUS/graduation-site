<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

class DegreeTable extends PaginatedTable
{
    public function __construct(DegreeSelect $degrees)
    {
        parent::__construct(
            $degrees,
            function (Degree $degree): array {
                return [
                    sprintf(
                        '<a href="%s">%s</a>',
                        new URL('/~degrees/search_by_netid.html?netid=' . $degree->netID()),
                        $degree->netID(),
                    ),
                    $degree->firstName(),
                    $degree->lastName(),
                    $degree->status(),
                    $degree->semester(),
                    $degree->level(),
                    $degree->college(),
                    [
                        'override' => $degree->override() ? 'YES' : null,
                        'department' => $degree->department(),
                        'program' => $degree->program(),
                        'major' => implode(', ', array_filter([
                            $degree->major1(),
                            $degree->major2() ?? false
                        ])),
                        'minor' => implode(', ', array_filter([
                            $degree->minor1() ?? false,
                            $degree->minor2() ?? false
                        ])),
                        'dissertation' => $degree->dissertation()
                    ]
                ];
            },
            [
                'NetID',
                'First name',
                'Last name',
                'Status',
                'Semester',
                'Level',
                'College',
                'Info'
            ]
        );
    }
}
