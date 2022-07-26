<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Graph;
use DigraphCMS\Spreadsheets\CellWriters\DateCell;
use DigraphCMS\Spreadsheets\CellWriters\LinkCell;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

class SignupWindow extends AbstractPage
{
    const DEFAULT_SLUG = '[uuid]';

    public function degreeLevels(): ?array
    {
        switch ($this->type()) {
            case 'terminal':
                return ['terminal'];
            case 'master':
                return ['master'];
            case 'undergrad':
                return ['bachelor', 'associate'];
        }
        return null;
    }

    public function rsvpReportHeaders(): array
    {
        $headers = [
            'Name',
            'Accommodations',
        ];
        // student degree
        if ($this->isForStudents()) $headers[] = 'Degree';
        // hooders
        if ($this->type() == 'terminal') {
            $headers[] = 'Hooder name';
            $headers[] = 'Hooder email';
            $headers[] = 'Assigned hooder';
        }
        // faculty columns
        if ($this->isForFaculty()) {
            $headers[] = 'Role';
            $headers[] = 'Regalia needed';
        }
        // created/updated go last
        $headers[] = new ColumnSortingHeader('Created', 'created');
        $headers[] = new ColumnSortingHeader('Updated', 'updated');
        return $headers;
    }

    public function rsvpReportCells(RSVP $rsvp): array
    {
        $cells = [
            $rsvp->url()->html(),
            $rsvp['accommodations.requested'] ? [
                'Phone' => $rsvp['accommodations.phone'],
                'Requested' => implode(', ', array_diff($rsvp['accommodations.needs'], ['other'])),
                'Other accommodations' => $rsvp['accommodations.extra'] ? '<br>extra info supplied, see RSVP page' : null
            ] : ''
        ];
        // student degree
        if ($this->isForStudents()) $cells[] = implode(', ', [$rsvp['degree']['college'], $rsvp['degree']['program']]);
        // hooders
        if ($this->type() == 'terminal') {
            $cells[] = $rsvp['hooder.name'];
            $cells[] = $rsvp['hooder.email'];
            $cells[] = $rsvp['hooder.assigned'] ? RSVPs::get($rsvp['hooder.assigned'])->url()->html() : '';
        }
        // faculty columns
        if ($this->isForFaculty()) {
            $cells[] = $rsvp['role'];
            $cells[] = $rsvp['regalia'] ? 'Y' : '';
        }
        // created/updated go last
        $cells[] = Format::date($rsvp->created());
        $cells[] = Format::date($rsvp->updated());
        return $cells;
    }

    public function rsvpReportDownloadHeaders(): array
    {
        $headers = [
            'Name',
            'Email',
            'Accommodations',
        ];
        // degree info for students
        if ($this->isForStudents()) {
            $headers[] = 'School/College';
            $headers[] = 'Department';
            $headers[] = 'Program';
        }
        // hooders
        if ($this->type() == 'terminal') {
            $headers[] = 'Dissertation';
            $headers[] = 'Hooder name';
            $headers[] = 'Hooder email';
            $headers[] = 'Assigned hooder';
        }
        // faculty columns
        if ($this->isForFaculty()) {
            $headers[] = 'Role';
            $headers[] = 'Regalia needed';
        }
        // created/updated go last
        $headers[] = 'Created';
        $headers[] = 'Updated';
        return $headers;
    }

    public function rsvpReportDownloadCells(RSVP $rsvp): array
    {
        $cells = [
            new LinkCell($rsvp->name(), $rsvp->url()),
            $rsvp->email(),
            $rsvp['accommodations.requested'] ? 'Y' : ''
        ];
        // degree info for students
        if ($this->isForStudents()) {
            $cells[] = $rsvp['degree.college'];
            $cells[] = $rsvp['degree.department'];
            $cells[] = $rsvp['degree.program'];
        }
        // hooders
        if ($this->type() == 'terminal') {
            $cells[] = $rsvp['degree.dissertation'];
            $cells[] = $rsvp['hooder.name'];
            $cells[] = $rsvp['hooder.email'];
            $cells[] = $rsvp['hooder.assigned'] ? new LinkCell(RSVPs::get($rsvp['hooder.assigned'])->name(), RSVPs::get($rsvp['hooder.assigned'])->url()) : '';
        }
        // faculty columns
        if ($this->isForFaculty()) {
            $cells[] = $rsvp['role'];
            $cells[] = $rsvp['regalia'] ? 'Y' : '';
        }
        // created/updated go last
        $cells[] = new DateCell($rsvp->created());
        $cells[] = new DateCell($rsvp->updated());
        return $cells;
    }

    public function isForFaculty(): bool
    {
        return in_array($this->type(), Config::get('commencement.faculty_signup_types'));
    }

    public function isForStudents(): bool
    {
        return in_array($this->type(), Config::get('commencement.student_signup_types'));
    }

    public function permissions(URL $url, ?User $user = null): ?bool
    {
        if ($url->action() == '_form') return Permissions::inGroup('users');
        elseif (substr($url->action(), 0, 7) == 'signup_') return Permissions::inGroup('users');
        if (!$this->isForStudents()) {
            if ($url->action() == 'reader_list') return false;
            if ($url->action() == 'name_cards') return false;
        }
        if ($this->type() != 'terminal') {
            if ($url->action() == 'hooder_assignments') return false;
        }
        return parent::permissions($url, $user);
    }

    public function commencement(): CommencementEvent
    {
        return Graph::parents($this->uuid(), 'normal')
            ->where('class = "commencement"')
            ->limit(1)
            ->fetch();
    }

    public static function create(?string $name, string $type, DateTime $start, DateTime $end): SignupWindow
    {
        $window = new SignupWindow([
            'type' => $type,
            'start' => $start->getTimestamp(),
            'end' => $end->getTimestamp()
        ]);
        $window->name($name);
        $window->setUnlisted(false);
        return $window;
    }

    public function pending(): bool
    {
        return new DateTime < $this->start();
    }

    public function ended(): bool
    {
        return new DateTime >= $this->end();
    }

    public function open(): bool
    {
        return !$this->pending() && !$this->ended();
    }

    public function update()
    {
        $this->updateName();
        return parent::update();
    }

    public function insert(?string $parent_uuid = null)
    {
        $this->updateName();
        return parent::insert($parent_uuid);
    }

    protected function updateName()
    {
        $this->name(
            $this['name']
                ?? Config::get('commencement.signup_types.' . $this->type())
                ?? ucfirst($this->type()) . ' RSVP'
        );
    }

    public function type(): string
    {
        return $this['type'];
    }

    public function setType(?string $type)
    {
        $this['type'] = $type;
        return $this;
    }

    public function start(): DateTime
    {
        return (new DateTime)->setTimestamp($this['start']);
    }

    public function end(): DateTime
    {
        return (new DateTime)->setTimestamp($this['end']);
    }

    public function unlisted(): bool
    {
        return !!$this['unlisted'];
    }

    public function setStart(DateTime $start)
    {
        $this['start'] = $start->getTimestamp();
        return $this;
    }

    public function setEnd(DateTime $end)
    {
        $this['end'] = $end->getTimestamp();
        return $this;
    }

    public function setUnlisted(bool $unlisted)
    {
        $this['unlisted'] = $unlisted ? 1 : 0;
        return $this;
    }

    public function routeClasses(): array
    {
        return ['commencement_signup', '_any'];
    }
}
