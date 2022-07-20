<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\UI\Templates;

class WaiverField extends FIELDSET
{
    protected $checkbox, $waiverText;

    public function __construct(string $label = 'Event waiver')
    {
        parent::__construct($label);
        $this->waiverText = (new DIV)
            ->setStyle('font-size', 'smaller')
            ->addChild(Templates::render('commencement/waiver.php'));
        $this->checkbox = (new CheckboxField('I have read and agree to the above'))
            ->setRequired(true);
        $this->addChild($this->waiverText);
        $this->addChild($this->checkbox);
    }

    public function value(bool $useDefault = false)
    {
        return $this->checkbox->value($useDefault);
    }

    public function default()
    {
        return $this->checkbox->default();
    }

    /**
     * @param boolean|null $default
     * @return $this
     */
    public function setDefault(?bool $default)
    {
        $this->checkbox->setDefault($default);
        return $this;
    }
}
