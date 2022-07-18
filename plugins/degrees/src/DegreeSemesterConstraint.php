<?php

namespace DigraphCMS_Plugins\unmous\degrees;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeSelect;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SemesterRange;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class DegreeSemesterConstraint
{
    protected $awarded, $pending;

    /**
     * Get the status/semester constraints for the degrees that should be allowed to sign up for Commencement for a given semester
     *
     * @param Semester $eventSemester
     * @return DegreeSemesterConstraint
     */
    public static function forCommencement(Semester $eventSemester): DegreeSemesterConstraint
    {
        if ($eventSemester->semester() == "Spring") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester->next()), new SemesterRange(null, $eventSemester->next()));
        elseif ($eventSemester->semester() == "Summer") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester), new SemesterRange(null, $eventSemester));
        elseif ($eventSemester->semester() == "Fall") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester), new SemesterRange(null, $eventSemester));
    }

    /**
     * Get the status/semester constraints for the degrees that should be allowed to sign up for Commencement for a given semester
     *
     * @param Semester $eventSemester
     * @param bool $allowSummerInSpring
     * @return DegreeSemesterConstraint
     */
    public static function forConvocation(Semester $eventSemester, bool $allowSummerInSpring): DegreeSemesterConstraint
    {
        if ($eventSemester->semester() == "Spring") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester->next()), new SemesterRange(null, $allowSummerInSpring ? $eventSemester->next() : $eventSemester));
        elseif ($eventSemester->semester() == "Summer") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester), new SemesterRange(null, $eventSemester));
        elseif ($eventSemester->semester() == "Fall") return new DegreeSemesterConstraint(new SemesterRange(null, $eventSemester), new SemesterRange(null, $eventSemester));
    }

    /**
     * Get the status/semester constraints for the degrees that should appear in the program for a given semester
     *
     * @param Semester $eventSemester
     * @return DegreeSemesterConstraint
     */
    public static function forCommencementProgram(Semester $eventSemester): DegreeSemesterConstraint
    {
        if ($eventSemester->isBefore(Semesters::current())) {
            // rules for past semesters (note that there are now pending degrees listed)
            if ($eventSemester->semester() == "Spring") return new DegreeSemesterConstraint(null, new SemesterRange($eventSemester, $eventSemester));
            elseif ($eventSemester->semester() == "Fall") return new DegreeSemesterConstraint(null, new SemesterRange($eventSemester->previous(), $eventSemester));
        } else {
            // rules for current/future semesters
            if ($eventSemester->semester() == "Spring") return new DegreeSemesterConstraint(new SemesterRange($eventSemester, $eventSemester), new SemesterRange($eventSemester, $eventSemester));
            elseif ($eventSemester->semester() == "Fall") return new DegreeSemesterConstraint(new SemesterRange($eventSemester->previous(), $eventSemester), new SemesterRange($eventSemester->previous(), $eventSemester));
        }
    }

    /**
     * Entering a null for a range will make nothing in that category allowed,
     * for example entering null for $pending would make a constraint that
     * matches no pending degrees.
     * 
     * If null is entered for both, this will match no degrees.
     *
     * @param SemesterRange|null $pending
     * @param SemesterRange|null $awarded
     */
    public function __construct(?SemesterRange $pending, ?SemesterRange $awarded)
    {
        $this->awarded = $awarded;
        $this->pending = $pending;
    }

    public function __toString()
    {
        if (!$this->awarded && !$this->pending) return 'none';
        elseif ($this->awarded == $this->pending) return 'awarded or pending ' . $this->awarded;
        else return implode(' and ', array_filter([
            $this->awarded ? 'awarded ' . $this->awarded : false,
            $this->pending ? 'pending ' . $this->pending : false
        ]));
    }

    public function contains(Semester $semester, string $status): bool
    {
        if ($status == 'awarded') return $this->awarded->contains($semester);
        elseif ($status == 'pending') return $this->pending->contains($semester);
        else return false;
    }

    public function containsDegree(Degree $degree): bool
    {
        return static::contains($degree->semester(), $degree->status());
    }

    public function degrees(): DegreeSelect
    {
        $query = Degrees::select();
        $clause = [];
        if ($this->awarded) $clause[] = $this->queryClause($this->awarded, 'awarded');
        if ($this->pending) $clause[] = $this->queryClause($this->pending, 'pending');
        if (!$clause) $clause[] = 'false'; // make a query that returns nothing if there are no
        $query->where(sprintf('(%s)', implode(' OR ', $clause)));
        return $query;
    }

    protected function queryClause(SemesterRange $range, string $onlyStatusType): string
    {
        $clause = [];
        // set status section to either specified value or OR clause to limit to pending/awarded
        $clause[] = sprintf('degreestatus = ?', DB::pdo()->quote($onlyStatusType));
        // set start/end clauses
        if ($range->start()) $clause = 'semester >= ' . $range->start()->intVal();
        if ($range->end()) $clause = 'semester <= ' . $range->start()->intVal();
        return sprintf('(%s)', implode(' AND ', $clause));
    }
}
