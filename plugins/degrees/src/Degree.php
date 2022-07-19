<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

class Degree
{
    protected $existing = false;
    protected $override;
    protected $userid, $netid, $firstname, $lastname;
    protected $status, $semester, $level, $college, $department, $program;
    protected $major1, $major2, $minor1, $minor2, $honors;
    protected $job;
    protected $dissertation;

    public static function fromImportRow(array $row, string $job = null, bool $override = false): Degree
    {
        // check if there's a privacy flag, skip if there's no privacy waiver
        if ($row['confidentiality indicator'] == 'Y') {
            if (!DB::query()->from('privacy_waiver')->where('netid = ?', [$row['netid']])->count()) {
                throw new \Exception('Privacy flag, no waiver');
            }
        }
        // userid
        $userID = md5($row['id'] . Config::get('secret'));
        // first name is either preferred or regular
        $firstName = trim($row['student preferred first name']);
        if (!$firstName) $firstName = $row['student first name'];
        // last name
        $lastName = trim($row['student last name']);
        // status comes from graduation status
        switch ($row['graduation status']) {
            case 'Pending':
                $row['graduation status'] = 'pending';
                break;
            case 'Hold Pending':
                $row['graduation status'] = 'pending';
                break;
            case 'Awarded':
                $row['graduation status'] = 'awarded';
                break;
            default:
                throw new \Exception("Unknown graduation status: " . $row['graduation status']);
                break;
        }
        // level comes from award category
        switch ($row['award category']) {
            case 'Associate Degree':
                $row['award category'] = 'associate';
                break;
            case 'Baccalaureate Degree':
                $row['award category'] = 'bachelor';
                break;
            case 'Doctoral Degree':
                $row['award category'] = 'terminal';
                break;
            case 'First-Professional Degree':
                $row['award category'] = 'terminal';
                break;
            case 'Masters Degree':
                $row['award category'] = 'master';
                break;
            case 'Post-Masters Degree':
                $row['award category'] = 'master';
                break;
            default:
                throw new \Exception("Unknown award category: " . $row['award category']);
                break;
        }
        // special handling for associates and branch campuses
        if ($row['college'] == 'Associate Degree') {
            if ($row['campus'] == 'Albuquerque/Main') throw new \Exception("Associates don't come from Albuquerque/Main");
            $row['college'] = "UNM - " . $row['campus'];
            if ($row['department'] == 'Provost Branch Campuses') $row['department'] = null;
        }
        // honors comes from either honor or commencement honors flag
        $honors = trim($row['honor']);
        if (!$honors) $honors = $row['commencement honors flag'];
        // return new object
        return new Degree(
            $userID,
            $row['netid'],
            $firstName,
            $lastName,
            $row['graduation status'],
            Semester::fromCode($row['academic period code']),
            $row['award category'],
            static::fixCollegeName($row['college']),
            $row['department'],
            $row['program'],
            $row['major'],
            $row['second major'],
            $row['first minor'],
            $row['second minor'],
            $honors ? ucwords(strtolower($honors)) : null,
            $job,
            static::fixDissertationTitle($row['dissertation title']),
            $override
        );
    }

    public function delete()
    {
        if ($id = $this->existing()) {
            DB::query()->delete('degree', $id)->execute();
        }
    }

    public function save()
    {
        // look in our system for preferred names
        if ($this->netID()) {
            $preferred = DB::query()->from('degree_preferred_name')->where('netid = ?', [$this->netID()])->fetch();
            if ($preferred) {
                $this->firstname = $preferred['first_name'] ?? $this->firstname;
                $this->lastname = $preferred['last_name'] ?? $this->lastname;
            }
        }
        // do actual saving
        if ($this->existing() !== null) $this->update();
        else $this->insert();
    }

    protected function existing(): ?int
    {
        if ($this->existing === false) {
            $existing = DB::query()->from('degree')
                ->where('override = ?', [$this->override])
                ->where('userid = ?', [$this->userid])
                ->where('semester = ?', [$this->semester])
                ->where('level = ?', [$this->level])
                ->where('college = ?', [$this->college])
                ->where('department = ?', [$this->department])
                ->where('program = ?', [$this->program])
                ->where('major1 = ?', [$this->major1])
                ->fetch();
            $this->existing = $existing
                ? $existing['id']
                : null;
        }
        return $this->existing;
    }

    protected function insert()
    {
        DB::query()->insertInto('degree', [
            'override' => $this->override(),
            'userid' => $this->userID(),
            'netid' => $this->netID(),
            'firstname' => $this->firstName(),
            'lastname' => $this->lastName(),
            'gradstatus' => $this->status(),
            'semester' => $this->semester()->intVal(),
            'honors' => $this->honors(),
            'level' => $this->level(),
            'college' => $this->college(),
            'department' => $this->department(),
            'program' => $this->program(),
            'major1' => $this->major1(),
            'major2' => $this->major2(),
            'minor1' => $this->minor1(),
            'minor2' => $this->minor2(),
            'dissertation' => $this->dissertation(),
            'job' => $this->job()
        ])->execute();
    }

    protected function update()
    {
        $row = [
            'firstname' => $this->firstName(),
            'lastname' => $this->lastName(),
            'gradstatus' => $this->status(),
            'honors' => $this->honors(),
            'major2' => $this->major2(),
            'minor1' => $this->minor1(),
            'minor2' => $this->minor2(),
            'dissertation' => $this->dissertation(),
            'job' => $this->job(),
        ];
        if ($this->netID()) $row['netid'] = $this->netID();
        DB::query()->update('degree', $row)
            ->where('id = ?', [$this->existing()])
            ->execute();
    }

    public function override(): bool
    {
        return $this->override;
    }

    public function userID(): string
    {
        return $this->userid;
    }

    public function netID(): ?string
    {
        return $this->netid;
    }

    public function firstName(): string
    {
        return $this->firstname;
    }

    public function lastName(): string
    {
        return $this->lastname;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function semester(): Semester
    {
        return Semester::fromCode($this->semester);
    }

    public function honors(): ?string
    {
        return $this->honors;
    }

    public function level(): string
    {
        return $this->level;
    }

    public function college(): string
    {
        return $this->college;
    }

    public function department(): ?string
    {
        return $this->department;
    }

    public function program(): string
    {
        return $this->program;
    }

    public function major1(): string
    {
        return $this->major1;
    }

    public function major2(): ?string
    {
        return $this->major2;
    }

    public function minor1(): ?string
    {
        return $this->minor1;
    }

    public function minor2(): ?string
    {
        return $this->minor2;
    }

    public function dissertation(): ?string
    {
        return $this->dissertation;
    }

    public function job(): ?string
    {
        return $this->job;
    }

    public static function fixCollegeName(?string $name): ?string
    {
        $name = trim($name);
        if (!$name) return null;
        return $name;
    }

    public static function fixDepartmentName(?string $name): ?string
    {
        $name = trim($name);
        if (!$name) return null;
        return $name;
    }

    public static function fixDissertationTitle(?string $title): ?string
    {
        $title = trim($title);
        // fix all upper case or all lower case
        if (strtoupper($title) == $title || strtolower($title) == $title) $title = ucwords(strtolower($title));
        if (!$title) return null;
        return $title;
    }

    public static function fromDatabaseRow(array $row): Degree
    {
        return new Degree(
            $row['userid'],
            $row['netid'],
            $row['firstname'],
            $row['lastname'],
            $row['gradstatus'],
            Semester::fromCode($row['semester']),
            $row['level'],
            $row['college'],
            $row['department'],
            $row['program'],
            $row['major1'],
            $row['major2'],
            $row['minor1'],
            $row['minor2'],
            $row['honors'],
            $row['job'],
            $row['dissertation'],
            !!$row['override']
        );
    }

    public function __construct(
        string $userid,
        ?string $netid,
        string $firstname,
        string $lastname,
        string $status,
        Semester $semester,
        string $level,
        string $college,
        ?string $department,
        string $program,
        string $major1,
        string $major2 = null,
        string $minor1 = null,
        string $minor2 = null,
        string $honors = null,
        string $job = null,
        string $dissertation = null,
        bool $override = false
    ) {
        $this->userid = $userid;
        $this->netid = trim($netid) ? strtolower(trim($netid)) : null;
        $this->firstname = trim($firstname);
        $this->lastname = trim($lastname);
        $this->status = strtolower(trim($status));
        $this->semester = $semester->intVal();
        $this->level = strtolower(trim($level));
        $this->college = trim($college);
        $this->department = trim($department) ? trim($department) : null;
        $this->program = trim($program);
        $this->major1 = trim($major1);
        $this->major2 = trim($major2) ? trim($major2) : null;
        $this->minor1 = trim($minor1) ? trim($minor1) : null;
        $this->minor2 = trim($minor2) ? trim($minor2) : null;
        $this->honors = trim($honors) ? trim($honors) : null;
        $this->job = trim($job) ? trim($job) : null;
        $this->dissertation = trim($dissertation) ? trim($dissertation) : null;
        $this->override = $override;
    }
}
