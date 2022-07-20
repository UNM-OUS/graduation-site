<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\UI\Templates;

class HooderField extends FIELDSET
{
    protected $name, $email;

    public function __construct(string $label)
    {
        parent::__construct($label);
        $this->name = new Field('Hooder name');
        $this->email = (new Field('Hooder email', new Email))
            ->addTip('Your hooder will be automatically emailed at this email address, informing them that you have requested them as a hooder and encouraging them to complete their own RSVP.');
        $this->addChild($this->name);
        $this->addChild($this->email);
        $this->addChild(Templates::render('commencement/signup/hooder_field_instructions.php'));
    }

    public function setDefault(?array $value)
    {
        $this->name->setDefault(@$value['name']);
        $this->email->setDefault(@$value['email']);
        return $this;
    }

    public function value($useDefault = false): array
    {
        return [
            'name' => $this->name->value($useDefault),
            'email' => $this->email->value($useDefault)
        ];
    }
}
