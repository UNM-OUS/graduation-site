<h1>Edit signup window</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

/** @var SignupWindow */
$window = Context::page();

$form = new FormWrapper('edit_' . Context::pageUUID());
$form->button()->setText('Save changes');

$name = (new Field('Name'))
    ->setDefault($window->name())
    ->setRequired(true)
    ->addForm($form);

$start = (new DatetimeField('Signup start'))
    ->setDefault($window->start())
    ->setRequired(true)
    ->addForm($form);

$end = (new DatetimeField('Signup end'))
    ->setDefault($window->end())
    ->setRequired(true)
    ->addForm($form);
$end->addValidator(function () use ($start, $end) {
    if ($start->value() > $end->value()) return "End date must be after start date";
    else return null;
});

$unlisted = (new CheckboxField('Make this form unlisted'))
    ->setDefault($window->unlisted())
    ->addTip('Unlisted signup forms are not shown in public pages, and you will need to send the link to anyone who should use it.')
    ->addForm($form);

if ($form->ready()) {
    $window->name($name->value());
    $window->setStart($start->value());
    $window->setEnd($end->value());
    $window->setUnlisted($unlisted->value());
    $window->update();
    Notifications::printConfirmation('Signup window updated');
    throw new RedirectException($window->url());
}

echo $form;
