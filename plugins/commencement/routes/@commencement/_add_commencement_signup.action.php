<h1>Add signup window</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;

Context::ensureUUIDArg(Pages::class);

$form = new FormWrapper('add_' . Context::arg('uuid'));
$form->button()->setText('Add signup window');

$type = new Field('Signup type', new SELECT(Config::get('commencement.signup_types')));
$type->required();
$form->addChild($type);

$name = new Field('Customize name');
$name->addTip('Leave blank unless you want to override the default naming convention.');
$form->addChild($name);

$start = new DatetimeField('Signup start');
$start->setRequired(true);
$form->addChild($start);

$end = new DatetimeField('Signup end');
$end->setRequired(true);
$end->addValidator(function () use ($start, $end) {
    if ($start->value() > $end->value()) return "End date must be after start date";
    else return null;
});
$form->addChild($end);

$unlisted = new CheckboxField('Make this form unlisted');
$unlisted->addTip('Unlisted signup forms are not shown in public pages, and you will need to send the link to anyone who should use it.');
$form->addChild($unlisted);

echo $form;
