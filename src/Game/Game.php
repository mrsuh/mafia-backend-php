<?php

namespace App\Game;

use App\Event\CitizensGreetingEvent;
use App\Event\EventFactory;
use App\Event\GameEvent;
use App\History\History;
use App\Player\Player;
use App\Player\Players;

class Game
{
    const MAFIA    = 'MAFIA';
    const CITIZENS = 'CITIZENS';

    private $id;
    private $players;
    private $history;
    private $eventFactory;
    private $winner;

    public function __construct()
    {
        $this->id = 767;
//        $this->id = random_int(100, 999);

        $this->players      = new Players();
        $this->history      = new History();
        $this->eventFactory = new EventFactory($this->history, $this->players, $this->id);
        $this->winner       = null;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return EventFactory
     */
    public function getEventFactory(): EventFactory
    {
        return $this->eventFactory;
    }

    /**
     * @return Players
     */
    public function getPlayers()
    {
        return $this->players;
    }

    public function isEnded()
    {
        if (1 === $this->eventFactory->getIteration()) {
            return false;
        }

        $mafiaCount    = 0;
        $citizensCount = 0;
        foreach ($this->players->getAll() as $player) {
            if ($player->getRole() === Player::ROLE_MAFIA) {
                $mafiaCount++;
                continue;
            }

            $citizensCount++;
        }

        if (0 === $mafiaCount) {
            $this->winner = static::CITIZENS;

            return true;
        }

        if (0 === $citizensCount) {
            $this->winner = static::MAFIA;

            return true;
        }

        return false;
    }

    public function getWinner()
    {
        return $this->winner;
    }
}
