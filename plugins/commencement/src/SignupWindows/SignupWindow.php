<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Graph;
use DigraphCMS_Plugins\unmous\commencement\CommencementEvent;

class SignupWindow extends AbstractPage
{
    const DEFAULT_SLUG = '[uuid]';

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
