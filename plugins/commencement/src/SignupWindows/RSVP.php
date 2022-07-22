<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Email\Email as EmailMessage;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeSemesterConstraint;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\AccommodationsField;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaRequestField;
use Flatrr\FlatArray;

class RSVP extends FlatArray
{
    protected $for, $window, $uuid;
    protected $form;
    protected $created, $created_by, $updated, $updated_by;

    public static function getFor(string $for, SignupWindow $window)
    {
        $uuid = static::computeUUID($for, $window->uuid());
        return RSVPs::get($uuid, $window)
            ?? new RSVP($for, $window);
    }

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
        $this->window = $window->uuid();
        $this->uuid = static::computeUUID($for, $window->uuid());
        // set created/updated
        $this->created = $created ?? new DateTime();
        $this->created_by = $created_by ?? Users::current() ?? Users::guest();
        $this->updated = $updated ?? new DateTime();
        $this->updated_by = $updated_by ?? Users::current() ?? Users::guest();
        // merge in provided data
        $this->merge($data, null, true);
    }

    protected static function computeUUID(string $for, string $windowUUID): string
    {
        return substr($windowUUID, 0, 4) . Digraph::uuid(null, implode(' ', [$for, $windowUUID]));
    }

    public function for(): string
    {
        return $this->for;
    }

    public function window(): SignupWindow
    {
        return SignupWindows::get($this->window);
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

    /**
     * @param boolean $cancelled
     * @return $this
     */
    public function setCancelled(bool $cancelled)
    {
        $this['cancelled'] = $cancelled;
        return $this;
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
        return $this->window()->url('signup_' . $this->uuid());
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

    public function exists(): bool
    {
        return !!DB::query()->from('commencement_signup')
            ->where('uuid = ?', [$this->uuid])
            ->count();
    }

    public function save(): bool
    {
        if ($this->exists()) {
            // update existing signup
            return !!DB::query()->update('commencement_signup', [
                'updated' => time(),
                'updated_by' => Session::uuid(),
                'data' => json_encode($this->get())
            ])->where('uuid = ?', $this->uuid)
                ->execute();
        } else {
            // insert new signup
            $out = !!DB::query()->insertInto('commencement_signup', [
                'uuid' => $this->uuid,
                '`for`' => $this->for,
                'window' => $this->window,
                'created' => $this->created()->getTimestamp(),
                'created_by' => $this->createdBy()->uuid(),
                'updated' => $this->updated()->getTimestamp(),
                'updated_by' => $this->updatedBy()->uuid(),
                'data' => json_encode($this->get())
            ])->execute();
            // send confirmation email to all interested parties
            if ($out) $this->sendNotificationEmail('created');
            // return result
            return $out;
        }
    }

    public function sendNotificationEmail(string $template, array $additionalRecipients = [])
    {
        $recipients = array_unique(array_merge(
            $this->notificationEmailRecipients(),
            array_map('strtolower', $additionalRecipients)
        ));
        foreach ($recipients as $address) {
            Emails::send(new EmailMessage(
                'service',
                Config::get('commencement.rsvp_email_subjects.' . $template) ?? 'Commencement RSVP notification',
                $address,
                null,
                'graduation@unm.edu',
                Templates::render("commencement/rsvp_emails/$template.php", ['rsvp' => $this]),
                null,
                null,
                'graduation@unm.edu'
            ));
        }
    }

    public function notificationEmailRecipients(): array
    {
        $recipients = [];
        // "for" field
        if (strpos('@', $this->for) !== false) $recipients[] = $this->for . '@unm.edu';
        else $recipients[] = $this->for;
        // contact email field
        if ($this['email']) $recipients = $this['email'];
        // creator primary email
        if ($this->createdBy()->primaryEmail()) $recipients[] = $this->createdBy()->primaryEmail();
        // updater primary email
        if ($this->updatedBy()->primaryEmail()) $recipients[] = $this->updatedBy()->primaryEmail();
        // make everything lower case
        $recipients = array_map('strtolower', $recipients);
        // return unique values
        return array_unique($recipients);
    }

    public function form(): FormWrapper
    {
        if (!$this->form) {
            // look up person info by $for and pre-fill whatever is possible by merging it into flatarray data
            $person = PersonInfo::fetch($this->for);
            if ($person) {
                $this->merge([
                    'name' => $person->fullName(),
                    'accommodations' => $person['accommodations'],
                    'email' => $person['email']
                ], null, false);
            }
            // specifically looking to prefill keys name, email, regalia, accommodations
            // first look in degrees for name
            if (!$this['name']) {
                $eligible = DegreeSemesterConstraint::forCommencement($this->window()->commencement()->semester())->degrees();
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
            if (in_array($this->window()->type(), Config::get('commencement.student_signup_types'))) {
                $name->addTip('This field is autopopulated automatically, but feel free to change it if you would like to have a different name read at the ceremony.');
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
            if (in_array($this->window()->type(), Config::get('commencement.faculty_signup_types'))) {
                $this->facultyForm($this->form);
            }
            // add student fields
            if (in_array($this->window()->type(), Config::get('commencement.student_signup_types'))) {
                $this->studentForm($this->form);
            }
            // add accommodations
            $accommodations = (new AccommodationsField('Accommodations', true))
                ->setDefault($this['accommodations']);
            $this->form->addChild($accommodations);
            // add waiver
            $waiver = new WaiverField;
            $this->form->addChild($waiver);
            // add callback
            $this->form->addCallback(function () use ($name, $pronunciation, $email, $accommodations, $waiver) {
                // update values from form
                $this['name'] = $name->value();
                if ($pronunciation) $this['pronunciation'] = $pronunciation->value();
                $this['email'] = $email->value();
                $this['accommodations'] = $accommodations->value();
                $this['waiver'] = $waiver->value();
                // save person info data
                PersonInfo::setFor($this->for, [
                    'fullname' => $name->value(),
                    'accommodations' => $accommodations->value(),
                    'phone' => @$accommodations->value()['phone'],
                    'email' => $email->value(),
                    'pronunciation' => $pronunciation ? $pronunciation->value() : null
                ]);
                // actually do writing
                $this->save();
            });
        }
        return $this->form;
    }

    protected function facultyForm(FormWrapper $form)
    {
        $role = (new Field('Role at Commencement', new SELECT(Config::get('commencement.faculty_roles'))))
            ->setDefault($this['role'] ?? PersonInfo::getFor($this->for, 'commencement_role'))
            ->setRequired(true)
            ->addForm($form);

        $regalia = (new RegaliaRequestField('Regalia rental', $this->for))
            ->setDefault($this['regalia'] ?? PersonInfo::getFor($this->for, 'regalia') ?? true)
            ->addTip('Academic regalia is required to attend commencement, if you do not check this box you must have your own regalia to wear.')
            ->addTip('If you have already ordered rental regalia through graduation.unm.edu for one or more convocations, please also order it here. The orders will be combined automatically.')
            ->addForm($form);

        $form->addCallback(function () use ($role, $regalia) {
            // set values
            $this['role'] = $role->value();
            $this['regalia'] = $regalia->value();
            // save into person info
            PersonInfo::setFor($this->for, [
                'regalia' => $regalia->value(),
                'commencement_role' => $role->value()
            ]);
        });
    }

    protected function studentForm(FormWrapper $form)
    {
        $degree = (new DegreeField('Degree to be recognized', $this->for, $this->window()->commencement()->semester()))
            ->setDefault($this['degree.id'])
            ->addTip('Due to time constraints students can only be recognized for a single degree, and only doctoral/terminal students will have their majors read.')
            ->addTip('Only your name is read for undergraduate and Master\'s degree students, and your selection here is only used to plan how much seating is necessary for your School/College.')
            ->setRequired(true)
            ->addForm($form);

        $hooder = null;
        if ($this->window()->type() == 'terminal') {
            $hooder = (new HooderField('Faculty hooder'))
                ->setDefault($this['hooder'] ?? PersonInfo::getFor($this->for, 'hooder'));
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
            // hooder stuff
            if ($hooder) {
                // if hooder information has changed, email hooder
                if ($hooder->value() != $this['hooder'] && $hooder->value()['email']) {
                    Emails::send(new EmailMessage(
                        'system',
                        'Commencement hooder request',
                        $hooder->value()['email'],
                        null,
                        'graduation@unm.edu',
                        Templates::render('commencement/rsvp_email/hooder_request.php', [
                            'commencement' => $this->window()->commencement(),
                            'hooder_name' => $hooder->value['name'],
                            'hooder_email' => $hooder->value['email'],
                            'student_name' => $this->name()
                        ]),
                        null,
                        $this->email(),
                        'graduation@unm.edu'
                    ));
                }
                // save hooder info
                unset($this['hooder']);
                $this['hooder'] = $hooder->value();
                // save hooder info into person info
                PersonInfo::setFor($this->netid, ['hooder' => $hooder->value() ?? false]);
            }
        });
    }
}
