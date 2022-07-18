<h1>Import degree records</h1>
<p>
    This tool is designed to ingest reports generated by MyReports.
    Multiple semesters can be included in one upload, and all the other filters should be set to "all" (invalid levels/degrees are automatically filtered out).
    "Dual Degrees For Selections Only" should be set to "No."
    "Confidentiality Indicator" should be set to "No Limit" (privacy flags/waivers are handled automatically).
    The import process expects the following columns (order is not important):
</p>
<ul style="font-size: smaller;line-height:1.4em;">
    <li>NetID</li>
    <li>Student First Name</li>
    <li>Student Preferred First Name</li>
    <li>Student Last Name</li>
    <li>Graduation Status</li>
    <li>Commencement Honors Flag</li>
    <li>Honor</li>
    <li>Award Category</li>
    <li>Campus</li>
    <li>College</li>
    <li>Department</li>
    <li>Program</li>
    <li>Major</li>
    <li>Second Major</li>
    <li>First Minor</li>
    <li>Second Minor</li>
    <li>Dissertation Title</li>
    <li>Academic Period Code</li>
    <li>Confidentiality Indicator</li>
    <li>ID</li>
</ul>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Spreadsheets\SpreadsheetReader;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\degrees\Degree;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

echo '<div class="notification-frame" data-target="_frame" id="upload_update_ui">';

if (Context::arg('job')) {
    echo "<h2>Running update job</h2>";
    echo (new DeferredProgressBar(Context::arg('job')))
        ->setDisplayAfter('Degree update complete');
    echo '<p>This process may take a long time, especially if there are many semesters in the file you uploaded.</p>';
} else {
    echo "<h2>Upload a spreadsheet to begin</h2>";
    Notifications::printWarning('Warning: Running multiple import jobs at once that include the same semester can cause unexpected results.');
    $form = new FormWrapper();

    $file = new UploadSingle();
    $fileField = new Field('File to upload', $file);
    $fileField->setRequired('true');
    $form->addChild($fileField);

    $replace = (new CheckboxField('Replace semesters'))
        ->setDefault(true)
        ->addTip('For each semester included in this file, remove all non-override degrees that don\'t appear in this file.')
        ->addTip('Usually you want this, because it means you can upload an entire semester from MyReports and it will both add new records and remove any that have disappeared.');
    $form->addChild($replace);

    if ($form->ready()) {
        $job = new SpreadsheetJob(
            $file->value()['tmp_name'],
            // function to import row
            function (array $row, DeferredJob $job) {
                try {
                    $degree = Degree::fromImportRow($row, $job->group());
                    $degree->save();
                    return $degree->netID() . ': added';
                } catch (\Throwable $th) {
                    return $row['netid'] . ': ' . $th->getMessage();
                }
            },
            // don't override anything
            null,
            null,
            null,
            // if replace is true, add teardown function to delete old records from these semesters
            !$replace->value()
                ? null
                : function (string $file, DeferredJob $job) {
                    $semesters = [];
                    foreach (SpreadsheetReader::rows($file) as $row) {
                        $semesters[] = intval($row['academic period code']);
                    }
                    $semesters = array_unique($semesters);
                    if ($semesters) {
                        $count = DB::query('degree')
                            ->delete('degree')
                            ->where('job <> ?', [$job->group()])
                            ->where('override <> 1')
                            ->where('semester in (' . implode(',', $semesters) . ')')
                            ->execute();
                        return sprintf(
                            "Teardown deleted %s degrees from semesters: %s",
                            $count,
                            implode(', ', array_map(
                                function (int $code) {
                                    return Semester::fromCode($code);
                                },
                                $semesters
                            ))
                        );
                    }
                    return "No semesters in data, nothing removed";
                }
        );
        throw new RedirectException(new URL('&job=' . $job->group()));
    }
    echo $form;
}

echo '</div>';
