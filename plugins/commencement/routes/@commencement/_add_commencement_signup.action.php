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
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

Context::ensureUUIDArg(Pages::class);

$form = new FormWrapper('add_' . Context::arg('uuid'));
$form->button()->setText('Add signup window');

$type = (new Field('Signup type', new SELECT(Config::get('commencement.signup_types'))))
    ->addTip('Cannot be changed once set')
    ->setRequired(true)
    ->addForm($form);

$name = (new Field('Name'))
    ->setRequired(true)
    ->addForm($form);

$start = (new DatetimeField('Signup start'))
    ->setRequired(true)
    ->addForm($form);

$end = (new DatetimeField('Signup end'))
    ->setRequired(true)
    ->addForm($form);
$end->addValidator(function () use ($start, $end) {
    if ($start->value() > $end->value()) return "End date must be after start date";
    else return null;
});

$unlisted = (new CheckboxField('Make this form unlisted'))
    ->addTip('Unlisted signup forms are not shown in public pages, and you will need to send the link to anyone who should use it.')
    ->addForm($form);

if ($form->ready()) {
    $window = new SignupWindow;
    $window->setUUID(Context::arg('uuid'));
    $window->setType($type->value());
    $window->name($name->value());
    $window->setStart($start->value());
    $window->setEnd($end->value());
    $window->setUnlisted($unlisted->value());
    $window->insert(Context::pageUUID());
    Notifications::printConfirmation('Signup window added');
    throw new RedirectException($window->url());
}

echo $form;
