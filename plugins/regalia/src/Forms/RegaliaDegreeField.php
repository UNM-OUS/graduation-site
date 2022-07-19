<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class RegaliaDegreeField extends FIELDSET
{
    protected $field, $notFound;

    public function __construct(string $label = 'Degree')
    {
        parent::__construct($label);
        $this->type = new Field('Degree type/level', new DegreeSelect());
        $this->type->setRequired(true);
        $this->addChild($this->type);
        $this->field = new Field('Degree field/discipline', new DegreeFieldInput());
        $this->field->addTip('The degree fields available here include many broad categories provided by Jostens for coloring regalia. If you cannot locate your exact major, please attempt to find a broader category under which your specific discipline logically belongs.');
        $this->addChild($this->field);
        $this->addClass('regalia-degree-field');
        $this->type->addClass('regalia-degree-field__type');
        $this->field->addClass('regalia-degree-field__field');
        // validator to make field required when type value starts with [preset]
        $this->field->addValidator(function () {
            if (substr($this->type->value(), 0, 8) != '[preset]') {
                if (!$this->field->value()) return 'This field is required';
            }
            return null;
        });
    }

    public function setDefault(array $value = null)
    {
        if ($value) {
            $preset = Regalia::preset($value['preset_id']);
            if ($preset['field_id']) {
                $preset = '[preset]' . $preset['id'];
                var_dump($preset);
                $this->type->setDefault($preset);
            } else {
                $preset = $preset['id'];
                $this->type->setDefault($preset);
                $this->field->setDefault($value['field_id']);
            }
        }
        return $this;
    }

    public function value(bool $useDefault = false)
    {
        $presetID = preg_replace('/^\[preset\]/', '', $this->type->value($useDefault));
        $preset = Regalia::preset($presetID);
        if (!$preset) return null;
        $fieldID = $preset['field_id'] ?? $this->field->value();
        if (!$fieldID) return null;
        return [
            'preset_id' => $presetID,
            'field_id' => $fieldID
        ];
    }
}
