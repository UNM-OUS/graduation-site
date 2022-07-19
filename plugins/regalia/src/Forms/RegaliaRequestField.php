<?php

namespace DigraphCMS_Plugins\unmous\ous_regalia\Forms;

use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS_Plugins\unmous\ous_regalia\Regalia;

class RegaliaRequestField extends FIELDSET
{
    protected $for, $infoForm, $optOut;

    public function __construct(string $label, string $for)
    {
        parent::__construct($label);
        $this->for = $for;
        $this->optOut = new CheckboxField('I do not need to rent regalia');
        $this->optOut->addClass('regalia-request-field__opt-out');
        $this->addChild($this->optOut);
        $this->infoForm = new RegaliaInformationForm($for);
        $this->infoForm->addClass('regalia-request-field__info-form');
        $this->addChild($this->infoForm);
        $this->addClass('regalia-request-field');
        // validator to require either opting out or an existing person record
        $this->optOut->addValidator(function () {
            if ($this->optOut->value()) return null;
            if (!Regalia::getPersonInfo($this->for)) return "You must either opt out of regalia rental or enter the information necessary to pick the regalia that you need";
        });
    }

    public function value(bool $useDefault = false)
    {
        return !$this->optOut->value($useDefault);
    }

    public function default(): bool
    {
        return !$this->optOut->default();
    }

    /**
     * Set default of whether regalia is requested
     *
     * @param boolean $default
     * @return $this
     */
    public function setDefault(bool $default)
    {
        $this->optOut->setDefault(!$default);
        return $this;
    }
}
