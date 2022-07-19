<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;

class RegaliaAlmaMaterField extends FIELDSET
{
    protected $institution, $notFound;

    public function __construct(string $label = 'Alma mater')
    {
        parent::__construct($label);
        $this->institution = new Field('Institution', new InstitutionInput());
        $this->addChild($this->institution);
        $this->notFound = new CheckboxField('I cannot locate the school where I got my degree');
        $this->notFound->addTip('Not all institutions are listed in the Jostens system from which our rental regalia is ordered.');
        $this->notFound->addTip('If you cannot find your alma mater in the above search box, check here and we will attempt to add it to our database using the most appropriate regalia available from Jostens. Someone may contact you directly for more information if necessary.');
        $this->addChild($this->notFound);
        $this->addClass('regalia-almamater-field');
        $this->institution->addClass('regalia-almamater-field__institution');
        $this->notFound->addClass('regalia-almamater-field__not-found');
        // validator to make institution required if not found checkbox isn't checked
        $this->institution->addValidator(function () {
            if ($this->notFound->value()) return null;
            if (!$this->institution->value()) return "Please specify an institution";
            return null;
        });
    }

    public function value(bool $useDefault = false)
    {
        if ($this->notFound->value($useDefault)) return false;
        return $this->institution->value($useDefault);
    }

    public function default()
    {
        if (!$this->notFound->default()) return false;
        return $this->institution->default();
    }

    public function setDefault($default)
    {
        if ($default === false) {
            $this->notFound->setDefault(true);
            $this->institution->setDefault(null);
        } elseif ($default === null) {
            $this->notFound->setDefault(false);
            $this->institution->setDefault(null);
        } else {
            $this->notFound->setDefault(false);
            $this->institution->setDefault($default);
        }
    }
}
