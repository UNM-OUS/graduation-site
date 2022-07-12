<?php

namespace DigraphCMS_Plugins\unmous\commencement;

use DateTime;
use DigraphCMS\Content\Page;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class CommencementEvent extends Page
{
    const DEFAULT_SLUG = '/[semester]_[uuid]';

    public static function create(Semester $semester, DateTime $time, ?string $location, string $type): CommencementEvent
    {
        return new CommencementEvent([
            'semester' => $semester->intVal(),
            'time' => $time->getTimestamp(),
            'location' => $location,
            'type' => $type
        ]);
    }

    public function parent(?URL $url = null): ?URL
    {
        if (!$url || $url->action() == 'index') {
            if ($this->isPast()) return new URL('/past_commencements/');
            elseif ($this->isUpcoming()) return new URL('/future_commencements/');
            else return new URL('/');
        } else return parent::parent($url);
    }

    public function current(): bool
    {
        return $this->semester()
            ->isEq(Semesters::current());
    }

    public function isPast(): bool
    {
        return $this->semester()
            ->isBefore(Semesters::current());
    }

    public function isUpcoming(): bool
    {
        return $this->semester()
            ->isAfter(Semesters::current());
    }

    public function slugVariable(string $name): ?string
    {
        switch ($name) {
            case 'semester':
                return $this->semester()->__toString();
            default:
                return parent::slugVariable($name);
        }
    }

    public function time(): DateTime
    {
        return (new DateTime())
            ->setTimestamp($this['time']);
    }

    public function location(): string
    {
        return $this['location'];
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
                ?? implode(' ', [
                    $this->semester(),
                    $this->type() == 'combined'
                        ? ''
                        : ucfirst($this->type()),
                    'Commencement'
                ])
        );
    }

    public function type(): string
    {
        return $this['type'];
    }

    public function semester(): Semester
    {
        return Semester::fromCode($this['semester']);
    }

    public function routeClasses(): array
    {
        return ['commencement', '_any'];
    }
}
