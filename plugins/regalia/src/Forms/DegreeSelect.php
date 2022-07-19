<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class DegreeSelect extends SELECT
{
    public function __construct()
    {
        $options = [];
        foreach (Regalia::presets() as $preset) {
            $id = $preset['id'];
            if ($preset['field_id']) {
                $id = "[preset]$id";
            }
            $options[$id] = $preset['label'];
        }
        parent::__construct($options);
    }
}
