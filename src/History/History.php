<?php

namespace App\History;

use App\Event\Event;

class History
{

    /**
     * @var Event[]
     */
    private $history;
    private $future;

    public function __construct()
    {
        $this->history = [];
        $this->future  = [];
    }

    /**
     * @return Event|null
     */
    public function getFuture()
    {
        return array_shift($this->future);
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function addFuture(Event $event)
    {
        $this->future[] = $event;

        return true;
    }

    public function findOneByTypeAndIteration(string $type, int $iteration)
    {
        foreach ($this->history as $event) {
            if ($event::EVENT === $type && $event->getIteration() === $iteration) {
                return $event;
            }
        }

        return null;
    }

    /**
     * @param string $type
     * @return Event[]
     */
    public function findByType(string $type)
    {
        $events = [];
        foreach ($this->history as $event) {
            if ($event::EVENT === $type) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * @param int $iteration
     * @return Event[]
     */
    public function findByIteration(int $iteration)
    {
        $events = [];
        foreach ($this->history as $event) {
            if ($event->getIteration() === $iteration) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * @return Event[]
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function addEvent(Event $event)
    {
        $this->history[] = $event;

        return true;
    }
}