<?php

namespace App\Event;

use App\Game\Game;
use App\History\History;
use App\Player\Player;
use App\Player\Players;

class EventFactory
{

    protected $iteration;
    protected $history;
    protected $players;
    protected $gameId;

    /**
     * @var Event
     */
    protected $current;

    public function __construct(History $history, Players $players, int $gameId, int $iteration = 1)
    {
        $this->iteration = $iteration;
        $this->history   = $history;
        $this->players   = $players;
        $this->gameId    = $gameId;
        $this->current   = new GameEvent($this->history, $this->players, $this->gameId);
    }

    /**
     * @return Event
     */
    public function getCurrent(): Event
    {
        return $this->current;
    }

    /**
     * @param Event $current
     */
    public function setCurrent(Event $current)
    {
        $this->current = $current;
    }

    public function incrIteration()
    {
        $this->iteration++;
    }

    public function getIteration()
    {
        return $this->iteration;
    }

    /**
     * @return Event|null
     * @throws \Exception
     */
    public function nextEvent()
    {
        if (!$this->current->isStarted()) {
            return null;
        }

        if (!$this->current->isProcessed()) {
            return null;
        }

        if (!$this->current->isEnded()) {
            return null;
        }

        $this->history->addEvent($this->current);

        switch ($this->current::EVENT) {
            case GameEvent::EVENT:
                $event = new DayEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case DayEvent::EVENT:

                if (1 === $this->iteration) {
                    $event = new CitizensGreetingEvent($this->history, $this->players, $this->gameId, $this->iteration);
                    break;
                }

                $event = new CourtEvent($this->history, $this->players, $this->gameId, $this->iteration);

                break;
            case CitizensGreetingEvent::EVENT:
                $event = new NightEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case NightEvent::EVENT:

                if (1 === $this->iteration) {
                    $event = new MafiaGreetingEvent($this->history, $this->players, $this->gameId, $this->iteration);
                    break;
                }

                $event = new MafiaEvent($this->history, $this->players, $this->gameId, $this->iteration);

                break;
            case MafiaGreetingEvent::EVENT:
                $this->iteration++;
                $event = new DayEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case CourtEvent::EVENT:
                $event = new NightEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case MafiaEvent::EVENT:
                $event = new DoctorEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case DoctorEvent::EVENT:
                $event = new GirlEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case GirlEvent::EVENT:
                $event = new SheriffEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            case SheriffEvent::EVENT:
                $event = new DayEvent($this->history, $this->players, $this->gameId, $this->iteration);
                break;
            default:
                throw new \Exception('invalid event');
        }

        $this->current = $event;

        return $event;
    }
}