<h1>Override degrees</h1>
<p>
    Override degrees are always listed as "pending" degree status.
    An override degree will allow a student to sign up, but it will not appear in the program.
    In order to keep the database tidy, this tool will also only allow adding overrides for degrees that have been seen before.
</p>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\degrees\Degree;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\SemesterField;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

echo "<h2>Add override degree</h2>";
echo "<div id='override-degree-tool' class='navigation-frame navigation-frame--stateless'>";

$form = new FormWrapper();
$form->button()->setText('Continue');

$semester = (new SemesterField('Semester', -1, 6, true))
    ->setDefault(Semesters::current())
    ->addForm($form);

$netid = (new Field('NetID', new NetIDInput))
    ->setRequired(true)
    ->addForm($form);

$firstName = (new Field('First name'))
    ->setRequired(true)
    ->addForm($form);

$lastName = (new Field('Last name'))
    ->setRequired(true)
    ->addForm($form);

$level = (new Field('Degree level', new SELECT([
    'associate' => "Associate's",
    'bachelor' => "Bachelor's",
    'master' => "Master's",
    'terminal' => "Doctoral/terminal"
])))
    ->setDefault('bachelor')
    ->setRequired(true)
    ->addForm($form);

if ($netid->value() && $firstName->value() && $lastName->value()) {
    $colleges = array_map(
        function ($row) {
            return $row['college'];
        },
        DB::query()->from('degree')
            // limit to 2 years before or after given semester, to not show defunct stuff
            ->where('semester >= ? AND semester <= ?', [$semester->value()->intval() - 200, $semester->value()->intval() + 200])
            // limit to given level
            ->where('level', $level->value())
            ->group('college')->fetchAll()
    );
    $colleges = array_combine($colleges, $colleges);
    $college = (new Field('School/College', new SELECT($colleges)))
        ->setRequired(true)
        ->addForm($form);
}

if (isset($college) && $college->value()) {
    $departments = array_map(
        function ($row) {
            return $row['department'];
        },
        DB::query()->from('degree')
            // limit to 4 years before or after given semester, to not show defunct stuff
            ->where('semester >= ? AND semester <= ?', [$semester->value()->intval() - 400, $semester->value()->intval() + 400])
            ->where('level = ?', [$level->value()])
            ->where('college = ?', [$college->value()])
            ->group('department')->fetchAll()
    );
    $departments = array_combine($departments, $departments);
    $department = (new Field('Department', new SELECT($departments)))
        ->setRequired(true)
        ->addForm($form);
}

if (isset($department) && $department->value()) {
    $programs = array_map(
        function ($row) {
            return $row['program'];
        },
        DB::query()->from('degree')
            // limit to 4 years before or after given semester, to not show defunct stuff
            ->where('semester >= ? AND semester <= ?', [$semester->value()->intval() - 400, $semester->value()->intval() + 400])
            ->where('level = ?', [$level->value()])
            ->where('college = ?', [$college->value()])
            ->where('department = ?', [$department->value()])
            ->group('program')->fetchAll()
    );
    $programs = array_combine($programs, $programs);
    $program = (new Field('Program', new SELECT($programs)))
        ->setRequired(true)
        ->addForm($form);
}

if (isset($program) && $program->value()) {
    $majors = array_map(
        function ($row) {
            return $row['major1'];
        },
        DB::query()->from('degree')
            ->where('level = ?', [$level->value()])
            ->where('college = ?', [$college->value()])
            ->where('department = ?', [$department->value()])
            ->where('program = ?', [$program->value()])
            ->group('major1')->fetchAll()
    );
    $majors = array_combine($majors, $majors);
    $major = (new Field('Major', new SELECT($majors)))
        ->setRequired(true)
        ->addForm($form);
    $form->button()->setText('Finish and save');
    $form->form()->setAttribute('data-target', '_top');
}

if (isset($major) && $major->value() && $form->ready()) {
    $degree = new Degree(
        md5($netid->value()),
        $netid->value(),
        $firstName->value(),
        $lastName->value(),
        'pending',
        $semester->value(),
        $level->value(),
        $college->value(),
        $department->value(),
        $program->value(),
        $major->value(),
        null,
        null,
        null,
        null,
        null,
        null,
        true
    );
    $degree->save();
    Notifications::flashConfirmation('Inserted degree');
    throw new RefreshException();
}

echo $form;

echo "</div>";

echo "<h2>Override degrees</h2>";
$degrees = Degrees::select()
    ->where('override = 1')
    ->order('id DESC');
$table = new PaginatedTable(
    $degrees,
    function (Degree $degree): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('/~degrees/search_by_netid.html?netid=' . $degree->netID()),
                $degree->netID(),
            ),
            $degree->firstName(),
            $degree->lastName(),
            $degree->semester(),
            $degree->level(),
            $degree->college(),
            [
                'department' => $degree->department(),
                'program' => $degree->program(),
                'major' => $degree->major1()
            ],
            (new CallbackLink(function () use ($degree) {
                $degree->delete();
            }))
                ->addChild('delete')
        ];
    },
    [
        'NetID',
        'First name',
        'Last name',
        'Semester',
        'Level',
        'College',
        'Info',
        ''
    ]
);
echo $table;
