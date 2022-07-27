<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class RegaliaInformationForm extends DIV
{
    protected $for;
    protected $form;

    public function __construct(string $for)
    {
        $this->for = $for;
        $this->addClass('navigation-frame navigation-frame--stateless');
        $this->setID('regalia-information-form--' . crc32($for));
    }

    public function setForm(FormWrapper $form)
    {
        $this->form = $form;
    }

    protected function currentChildObject(): Tag
    {
        if (Context::arg($this->id()) == 'edit') {
            return $this->buildForm();
        } else {
            return $this->buildDisplay();
        }
    }

    protected function buildDisplay(): Tag
    {
        $editURL = new URL('&' . $this->id() . '=edit');
        $div = new DIV;
        if ($info = Regalia::getPersonInfo($this->for)) {
            // $div->addChild('<pre>' . print_r($info, true) . '</pre>');
            $preset = Regalia::preset($info['preset_id']);
            $field = Regalia::field($info['field_id']);
            $institution = $info['institution_id'] ? Regalia::institution($info['institution_id']) : null;
            $div->addChild(sprintf(
                '<div><strong>Needed parts:</strong> %s</div>',
                implode(', ', array_filter([
                    $info['needs_hat'] ? 'hat' : false,
                    $info['needs_robe'] ? 'robe' : false,
                    $info['needs_hood'] ? 'hood' : false,
                ]))
            ));
            $div->addChild(sprintf(
                '<div><strong>Degree: </strong> %s%s</div>',
                $preset['label'],
                !@$preset['preset'] ? ' (' . $field['label'] . ')' : ''
            ));
            $div->addChild(sprintf(
                '<div><strong>Alma mater: </strong> %s</div>',
                $institution ? $institution['label'] : '<em>not found</em>'
            ));
            $div->addChild(sprintf(
                '<div><strong>Robe size: </strong> %s\' %s", %slbs%s</div>',
                floor($info['size_height'] / 12),
                $info['size_height'] % 12,
                $info['size_weight'],
                ['M' => ', Masculine', 'F' => ', Feminine', 'O' => ''][$info['size_gender']],
            ));
            $div->addChild(sprintf(
                '<div><strong>Hat size: </strong> %s</div>',
                $info['size_hat']
            ));
            $div->addChild(sprintf('<p><small><a href="%s">Edit regalia information</a></small></p>', $editURL));
            return $div;
        } else {
            $div->addChild('<p>No regalia information on file. Please complete this section to enter your regalia sizing and degree information. Your information will be automatically saved for next time you need to rent regalia.</p>');
            $div->addChild(sprintf('<a href="%s" class="button">Enter regalia information</a>', $editURL));
            return $div;
        }
    }

    protected function buildForm(): FormWrapper
    {
        $form = new FormWrapper($this->id() . '__form');

        $parts = new CheckboxListField('Which pieces of regalia do you need to rent?', [
            'hat' => 'Hat',
            'robe' => 'Robe',
            'hood' => 'Hood'
        ]);
        $parts->setRequired(true);
        $form->addChild($parts);

        $degree = new RegaliaDegreeField();
        $form->addChild($degree);

        $almaMater = new RegaliaAlmaMaterField();
        $form->addChild($almaMater);

        $size = new RegaliaSizeField();
        $form->addChild($size);

        if ($person = Regalia::getPersonInfo($this->for)) {
            $parts->setDefault(array_filter([
                $person['needs_hat'] ? 'hat' : false,
                $person['needs_robe'] ? 'robe' : false,
                $person['needs_hood'] ? 'hood' : false,
            ]));
            $degree->setDefault([
                'preset_id' => $person['preset_id'],
                'field_id' => $person['field_id']
            ]);
            $almaMater->setDefault($person['institution_id']);
            $size->setDefault([
                'height' => $person['size_height'],
                'weight' => $person['size_weight'],
                'gender' => $person['size_gender'],
                'hat' => $person['size_hat']
            ]);
        }

        $form->addCallback(function () use ($parts, $degree, $almaMater, $size) {
            $value = [
                'identifier' => $this->for,
                'preset_id' => $degree->value()['preset_id'],
                'field_id' => $degree->value()['field_id'],
                'institution_id' => $almaMater->value(),
                'needs_hat' => in_array('hat', $parts->value()),
                'needs_robe' => in_array('robe', $parts->value()),
                'needs_hood' => in_array('hood', $parts->value()),
                'size_height' => $size->value()['height'],
                'size_weight' => $size->value()['weight'],
                'size_gender' => $size->value()['gender'],
                'size_hat' => $size->value()['hat']
            ];
            if ($person = Regalia::getPersonInfo($this->for)) {
                Regalia::query()
                    ->update('regalia_person', $value)
                    ->where('identifier = ?', [$this->for])
                    ->execute();
            } else {
                Regalia::query()
                    ->insertInto('regalia_person', $value)
                    ->execute();
            }
            throw new RedirectException(new URL('&' . $this->id() . '=view'));
        });
        $form->button()->setText('Save regalia information');
        return $form;
    }

    public function children(): array
    {
        return array_merge(
            [$this->currentChildObject()],
            parent::children()
        );
    }
}
