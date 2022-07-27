<h1>Hooder assignments</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVPs;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\SignupWindow;

/** @var SignupWindow */
$window = Context::page();

$students = RSVPs::select(Context::page())
    ->order('CASE WHEN ${data.hooder.assigned} IS NULL THEN 0 ELSE 1 END')
    ->order('${data.degree.college}')
    ->order('${data.degree.department}')
    ->order('${data.degree.major1}')
    ->order('${data.name}');

$facultyWindows = $window->commencement()->signupWindows()->where('${data.type}', Config::get('commencement.faculty_signup_types'));
if (!$facultyWindows->count()) {
    Notifications::printError('No faculty signup windows to pull hooder info from');
    return;
}
$facultyWindowIDs = array_map(
    function (SignupWindow $window) {
        return $window->uuid();
    },
    $facultyWindows->fetchAll()
);

$facultyRSVPs = RSVPs::select()
    ->where('window', $facultyWindowIDs);
if (!$facultyRSVPs->count()) {
    Notifications::printError('No faculty signups to assign hooders from');
    return;
}


$table = new PaginatedTable(
    $students,
    function (RSVP $student) use ($facultyWindowIDs) {
        $cells = [
            [
                'Name' => $student->url()->html(),
                'School/College' => $student['degree.college'],
                'Department' => $student['degree.department'],
                'Program' => $student['degree.program'],
                'Major' => $student['degree.major1'],
            ],
            implode(', ', array_filter([$student['hooder.name'] ?? false, $student['hooder.email'] ?? false]))
        ];
        $hooder = RSVPs::get($student['hooder.assigned']);
        if ($hooder) {
            $cells[] = [
                'Name' => $hooder->url()->html(),
                'School/College/Org' => $hooder['unm.org'],
                'Department' => $hooder['unm.department']
            ];
            $cells[] = (new CallbackLink(function () use ($student) {
                unset($student['hooder.assigned']);
                $student->save();
            }))
                ->setID($student->uuid())
                ->setData('target', '_frame')
                ->addChild('unassign');
        } else {
            // determine likely hooders for this student
            $hooders = RSVPs::select()
                ->where('window', $facultyWindowIDs);
            $hooders->order('CASE WHEN ${data.email} = ? THEN 0 ELSE 1 END', [$student['hooder.email']]);
            $hooders->order('CASE WHEN ${data.name} = ? THEN 0 ELSE 1 END', [$student['hooder.name']]);
            $hooders->order('CASE WHEN ${data.unm.org} = ? THEN 0 ELSE 1 END', [$student['degree.college']]);
            $hooders->order('CASE WHEN ${data.unm.department} = ? THEN 0 ELSE 1 END', [$student['degree.department']]);
            $hooders->order('CASE WHEN ${data.role} = "hooder" THEN 0 ELSE 1 END');
            $hooders->order('CASE WHEN ${data.role} = "platform" THEN 1 ELSE 0 END');
            // turn those results into a dropdown
            $form = new FormWrapper($student->uuid());
            $form->addClass('inline-autoform');
            $form->setData('target', '_frame');
            $options = [
                null => '-- none --'
            ];
            while ($hooder = $hooders->fetch()) {
                $options[$hooder->uuid()] = $hooder->name();
            }
            $select = new SELECT($options);
            $form->addChild($select);
            // form callback
            $form->addCallback(function () use ($student, $select) {
                $student['hooder.assigned'] = $select->value();
                $student->save();
                throw new RefreshException();
            });
            // add form to cells
            $cells[] = $form;
            // add blank last cell
            $cells[] = '';
        }
        return $cells;
    },
    [
        'Student',
        'Requested hooder',
        'Assigned hooder',
        ''
    ]
);

echo $table;
