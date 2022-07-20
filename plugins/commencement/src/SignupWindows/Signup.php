<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\AccommodationsField;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaRequestField;
use Flatrr\FlatArray;

class Signup extends FlatArray
{
    protected $for, $window, $uuid;
    protected $form;
    protected $created, $created_by, $updated, $updated_by;

    public function __construct(
        string $for,
        SignupWindow $window,
        array $data = [],
        User $created_by = null,
        DateTime $created = null,
        User $updated_by = null,
        DateTime $updated = null
    ) {
        $this->for = $for;
        $this->window = $window;
        $this->uuid = substr($window->uuid(), 0, 4) . Digraph::uuid(null, implode(' ', [$for, $window->uuid()]));
        // set created/updated
        $this->created = $created ?? new DateTime();
        $this->created_by = $created_by ?? Users::current() ?? Users::guest();
        $this->updated = $updated ?? new DateTime();
        $this->updated_by = $updated_by ?? Users::current() ?? Users::guest();
        // merge in provided data
        $this->merge($data, null, true);
    }

    public function name(): string
    {
        return htmlspecialchars($this['name']);
    }

    public function pronunciation(): string
    {
        return htmlspecialchars($this['pronunciation']);
    }

    public function email(): string
    {
        return htmlspecialchars($this['email']);
    }

    public function cancelled(): bool
    {
        return !!$this['cancelled'];
    }

    public function hooder(): array
    {
        $hooder = [];
        if ($this['hooder.name']) $hooder['name'] = htmlspecialchars($this['hooder.name']);
        if ($this['hooder.email']) $hooder['email'] = htmlspecialchars($this['hooder.email']);
        return $hooder;
    }

    public function accommodationsString(): ?string
    {
        if (!$this['accommodations.requested']) return null;
        $out = implode(', ', $this['accommodations.needs']);
        if ($this['accommodations.extra']) $out .= '<br>' . preg_replace('/[\r\n]+/', '<br>', htmlspecialchars($this['accommodations.extra']));
        if ($this['accommodations.phone']) $out .= '<br>' . htmlspecialchars($this['accommodations.phone']);
        return $out;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function url(): URL
    {
        return $this->window->url('signup_' . $this->uuid());
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }

    public function createdBy(): User
    {
        return $this->created_by;
    }

    public function updatedBy(): User
    {
        return $this->updated_by;
    }

    public function save(): bool
    {
        if (DB::query()->from('commencement_signup')->where('uuid = ?', [$this->uuid])->count()) {
            // update existing signup
            return !!DB::query()->update('commencement_signup', [
                'updated' => time(),
                'updated_by' => Session::uuid(),
                'data' => json_encode($this->get())
            ])->where('uuid = ?', $this->uuid)
                ->execute();
        } else {
            // insert new signup
            return !!DB::query()->insertInto('commencement_signup', [
                'uuid' => $this->uuid,
                'for' => $this->for,
                'window' => $this->window->uuid(),
                'created' => $this->created()->getTimestamp(),
                'created_by' => $this->createdBy()->uuid(),
                'updated' => $this->updated()->getTimestamp(),
                'updated_by' => $this->updatedBy()->uuid(),
                'data' => json_encode($this->get())
            ])->execute();
        }
    }

    public function form(): FormWrapper
    {
        if (!$this->form) {
            // TODO: look up user by $for and pre-fill whatever is possible by merging it into flatarray data
            // specifically looking to prefill keys name, email, regalia, accommodations
            // first look in degrees for name
            if (!$this['name']) {
                $eligible = DegreeSemesterConstraint::forCommencement($this->window->commencement()->semester())->degrees();
                $eligible->where('netid = ?', [$this->for]);
                if ($degree = $eligible->fetch()) {
                    $this['name'] = $this['name'] ?? $degree->firstName() . ' ' . $degree->lastName();
                }
            }
            // try to guess email from "for" value if email is missing
            $this['email'] = $this['email']
                ?? (strpos($this->for, '@') ? $this->for : $this->for . "@unm.edu");
            // set up new form object
            $this->form = new FormWrapper($this->uuid);
            // add fields that are always included
            $name = (new Field('Name'))
                ->setDefault($this['name'])
                ->setRequired(true)
                ->addForm($this->form);
            // add pronunciation for students only
            $pronunciation = null;
            if (in_array($this->window->type(), Config::get('commencement.student_signup_types'))) {
                $name->addTip('This field is autopopulated with the best name we could locate for you, but feel free to change it if you would like to have a different name read at the ceremony.');
                $pronunciation = (new Field('Name pronunciation'))
                    ->addTip('Instructions for the readers to help them pronounce your name, if you are worried they may not do so correctly')
                    ->addTip('For example Ada Lovelace might enter "Aye-Duh Luv-Lace" or Lobo Lucy might enter "Low-Bow Loo-See"')
                    ->setDefault($this['pronunciation'])
                    ->setRequired(false)
                    ->addForm($this->form);
            }
            $email = (new Field('Contact email', new Email))
                ->setDefault($this['email'])
                ->setRequired(true)
                ->addForm($this->form);
            // add faculty fields
            if (in_array($this->window->type(), Config::get('commencement.faculty_signup_types'))) {
                $this->facultyForm($this->form);
            }
            // add student fields
            if (in_array($this->window->type(), Config::get('commencement.student_signup_types'))) {
                $this->studentForm($this->form);
            }
            // add accommodations
            $accomodations = (new AccommodationsField('Accommodations', true))
                ->setDefault($this['accommodations']);
            $this->form->addChild($accomodations);
            // add waiver
            $waiver = (new WaiverField)
                ->setDefault($this['waiver']);
            $this->form->addChild($waiver);
            // add callback
            $this->form->addCallback(function () use ($name, $pronunciation, $email, $accomodations, $waiver) {
                // update values from form
                $this['name'] = $name->value();
                if ($pronunciation) $this['pronunciation'] = $pronunciation->value();
                $this['email'] = $email->value();
                $this['accommodations'] = $accomodations->value();
                $this['waiver'] = $waiver->value();
                // actually do writing
                $this->save();
            });
        }
        return $this->form;
    }

    protected function facultyForm(FormWrapper $form)
    {
        $role = (new Field('Role at Commencement', new SELECT(Config::get('commencement.faculty_roles'))))
            ->setDefault($this['role'])
            ->setRequired(true);

        $regalia = (new RegaliaRequestField('Regalia rental', $this->for))
            ->setDefault($this['regalia'] ?? true)
            ->addForm($form);

        $form->addCallback(function () use ($role, $regalia) {
            $this['role'] = $role->value();
            $this['regalia'] = $regalia->value();
        });
    }

    protected function studentForm(FormWrapper $form)
    {
        $degree = (new DegreeField('Degree to be recognized', $this->for, $this->window->commencement()->semester()))
            ->setDefault($this['degree.id'])
            ->addTip('Due to time constraints students can only be recognized for a single degree, and only doctoral/terminal students will have their majors read.')
            ->addTip('Only your name is read for undergraduate and Master\'s degree students, and your selection here is only used to plan how much seating is necessary for your School/College.')
            ->setRequired(true)
            ->addForm($form);

        $hooder = null;
        if ($this->window->type() == 'terminal') {
            $hooder = (new HooderField('Faculty hooder'))
                ->setDefault($this['hooder']);
            $form->addChild($hooder);
        }

        $form->addCallback(function () use ($degree, $hooder) {
            // cache degree data that we might actually need into the signup, so it's easier to make reports
            $degree = Degrees::get($degree->value());
            $this['degree'] = [
                'id' => $degree->id(),
                'level' => $degree->level(),
                'college' => $degree->college(),
                'department' => $degree->department(),
                'program' => $degree->program(),
                'major1' => $degree->major1(),
                'dissertation' => $degree->dissertation()
            ];
            // save hooder info
            if ($hooder) $this['hooder'] = $hooder->value();
        });
    }
}
