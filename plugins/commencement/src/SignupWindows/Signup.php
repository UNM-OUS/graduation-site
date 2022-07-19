<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\AccommodationsField;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaRequestField;
use Flatrr\FlatArray;

class Signup extends FlatArray
{
    protected $for, $window, $uuid;
    protected $form;

    public function __construct(string $for, SignupWindow $window, array $data = [])
    {
        $this->for = $for;
        $this->window = $window;
        $this->uuid = Digraph::uuid(null, implode(' ', [$for, $window->uuid()]));
        // merge in provided data
        $this->merge($data, null, true);
    }

    public function form(): FormWrapper
    {
        if (!$this->form) {
            // TODO: look up user by $for and pre-fill whatever is possible by merging it into flatarray data
            // specifically looking to prefill keys name, email, regalia, accommodations
            // try to guess email from "for" value if email is missing
            $this['email'] = $this['email']
                ?? (strpos($this->for, '@') ? $this->for : $this->for . "@unm.edu");
            // set up new form object
            $this->form = new FormWrapper($this->uuid);
            // add fields that are always included
            $name = (new Field('Name'))
                ->setDefault($this['name'])
                ->setRequired(true)
                ->addForm($this->form);
            $email = (new Field('Contact email', new Email))
                ->setDefault($this['email'])
                ->setRequired(true)
                ->addForm($this->form);
            // add faculty fields
            if (in_array($this->window->type(), Config::get('commencement.faculty_signup_types'))) {
                $this->facultyForm($this->form);
            }
            // add accomodations
            $accomodations = (new AccommodationsField('Accommodations', true))
                ->setDefault($this['accommodations']);
            $this->form->addChild($accomodations);
            // add waiver
            $waiver = (new WaiverField)
                ->setDefault($this['waiver']);
            $this->form->addChild($waiver);
            // add callback
            $this->form->addCallback(function () use ($name, $email, $accomodations, $waiver) {
                $this['name'] = $name->value();
                $this['email'] = $email->value();
                $this['accommodations'] = $accomodations->value();
                $this['waiver'] = $waiver->value();
            });
        }
        return $this->form;
    }

    protected function facultyForm(FormWrapper $form)
    {
        $role = (new Field('Role at Commencement', new SELECT(Config::get('commencement.faculty_roles'))))
            ->setDefault($this['role'])
            ->setRequired(true);
        $regalia = (new RegaliaRequestField('Regalia rental', $this->for))
            ->setDefault($this['regalia'] ?? true)
            ->addForm($form);
        $form->addCallback(function () use ($role, $regalia) {
            $this['role'] = $role->value();
            $this['regalia'] = $regalia->value();
        });
    }
}
