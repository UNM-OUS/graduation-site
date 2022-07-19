<h1>Add order group</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;

$form = new FormWrapper();

$name = (new Field('Order name'))
    ->setRequired(true);
$form->addChild($name);

$bookstoreDeadline = (new DatetimeField('Bookstore deadline'))
    ->addTip('If specified, this date will be the date after which the initial orders are placed with the bookstore and further requests will need to be filled by extras.');
$form->addChild($bookstoreDeadline);

$finalDeadline = (new DatetimeField('Final deadline'))
    ->setRequired(true)
    ->addTip('This final deadline is the date after which no one can submit new requestes, even waitlist requests.');
$form->addChild($finalDeadline);

echo $form;
