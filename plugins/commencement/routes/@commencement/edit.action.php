<h1>Edit Commencement</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

/** @var CommencementEvent */
$event = Context::page();

// set up form
$form = new FormWrapper('edit_' . Context::pageUUID());
$form->button()->setText('Add Commencement');

// date/time field
$date = (new DatetimeField('Event start date and time'))
    ->setDefault($event->time())
    ->setRequired(true);
$form->addChild($date);

// validator to ensure date is within the selected semester
$date->addValidator(function () use ($date, $semester) {
    $start = $semester->value()->start();
    $end = $semester->value()->end();
    if ($date->value() < $start || $date->value() > $end) {
        return sprintf(
            'Must be between %s and %s',
            Format::date($start, false, true),
            Format::date($end, false, true)
        );
    } else {
        return null;
    }
});

// location field
$location = (new Field('Location'))
    ->setDefault($event->location());
$form->addChild($location);

// custom name field
$name = (new Field('Customize name'))
    ->setDefault($event['name'])
    ->addTip('Leave this field blank unless you need to override the default naming scheme.');
$form->addChild($name);

// body text
$body = (new RichContentField('Body content', Context::pageUUID()))
    ->setDefault($event->richContent('body'))
    ->addTip('Add any extra content that should be listed on this event\'s page, such as keynote speakers, links to programs, special instructions, etc.')
    ->setRequired(false);
$form->addChild($body);

// handle form
if ($form->ready()) {
    $event['name'] = $name->value();
    $event['time'] = $date->value()->getTimestamp();
    $event['location'] = $location->value();
    $event->richContent('body', $body->value());
    $event->update();
    // redirect to event URL
    throw new RedirectException($event->url());
}

echo $form;
