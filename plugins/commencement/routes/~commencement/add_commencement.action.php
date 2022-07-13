<h1>Add Commencement</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\SemesterField;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

// ensure we have a UUID in the parameters
if (!Context::arg('uuid')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// validate parameter UUID
if (!Digraph::validateUUID(Context::arg('uuid') ?? '')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// ensure parameter UUID doesn't already exist
if (Pages::exists(Context::arg('uuid'))) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// set up form
$form = new FormWrapper('add_' . Context::arg('uuid'));
$form->button()->setText('Add Commencement');

// semester field
$semester = (new SemesterField('Semester'))
    ->setRequired(true)
    ->addTip('Event becomes "current" on the homepage on the first day of this semester.')
    ->addTIp('Cannot be changed once set.');
if (Context::arg('semester')) {
    $set = Semester::fromCode(Context::arg('semester'));
    $semester->input()->setOption($set, $set->__toString());
    $semester->setDefault($set);
}
$form->addChild($semester);

// date/time field
$date = (new DatetimeField('Event start date and time'))
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
$location = new Field('Location');
$form->addChild($location);

// type field
$typeInput = new SELECT([
    'combined' => 'Combined',
    'graduate' => 'Graduate',
    'undergraduate' => 'Undergraduate'
]);
$type = (new Field('Type', $typeInput))
    ->setRequired(true)
    ->addTIp('Cannot be changed once set.');
$form->addChild($type);

// custom name field
$name = (new Field('Customize name'))
    ->addTip('Leave this field blank unless you need to override the default naming scheme.');
$form->addChild($name);

// body text
$body = (new RichContentField('Body content', Context::arg('uuid')))
    ->addTip('Add any extra content that should be listed on this event\'s page, such as keynote speakers, links to programs, special instructions, etc.')
    ->setRequired(false);
$form->addChild($body);

// default windows
$defaultWindows = (new CheckboxField('Add default signup windows'))
    ->setDefault(true);
$form->addChild($defaultWindows);

// handle form
if ($form->ready()) {
    DB::beginTransaction();
    // insert event
    $event = CommencementEvent::create(
        $semester->value(),
        $date->value(),
        $location->value(),
        $type->value()
    );
    $event['name'] = $name->value();
    $event->setUUID(Context::arg('uuid'));
    $event->richContent('body', $body->value());
    $event->insert();
    // insert default signup windows
    if ($defaultWindows->value()) {
        foreach (Config::get('commencement.default_signups') as $s) {
            $window = SignupWindow::create(
                null,
                $s['type'],
                $event->time()->modify($s['start'])->setTime(9, 0, 0, 0),
                $event->time()->modify($s['end'])->setTime(17, 0, 0, 0)
            );
            if (@$s['unlisted']) $window->setUnlisted(true);
            $window->insert($event->uuid());
        }
    }
    // redirect to event URL
    DB::commit();
    throw new RedirectException($event->url());
}

echo $form;
