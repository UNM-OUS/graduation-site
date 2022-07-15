<h1>Search by NetID</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;

$form = new FormWrapper();
$form->addClass('inline-form');
$form->button()->setText('Search');

$query = (new NetIDInput())
    ->setDefault(Context::arg('netid'))
    ->setRequired(true);
$form->addChild($query);

if ($form->ready()) throw new RedirectException(new URL('search_by_netid.html?netid=' . $query->value()));

echo $form;

// display results
if (!($netid = Context::arg('netid'))) return;

// degree records
echo "<h2>Degree records</h2>";
echo new DegreeTable(Degrees::selectAny()
    ->where('netid = ?', [$netid]));
