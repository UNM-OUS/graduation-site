<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

class DegreeField extends Field
{
    public function __construct(string $label, string $netid, Semester $semester, $level = null)
    {
        $options = [];
        $eligible = DegreeSemesterConstraint::forCommencement($semester)->degrees();
        $eligible->where('netid', $netid);
        if ($level) $eligible->where('level', $level);
        while ($degree = $eligible->fetch()) {
            $options[$degree->id()] = implode(', ', array_filter(
                [
                    $degree->college(),
                    $degree->program(),
                    $degree->semester()
                ],
                function ($e) {
                    return !!$e;
                }
            ));
        }
        parent::__construct($label, new SELECT($options));
    }
}
