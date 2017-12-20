<?php

namespace App\Event;

use App\History\History;
use App\Player\Players;

class NightEvent extends Event
{
    const EVENT = 'night';

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->eventPlayers = $this->players->getAll();
        $this->processed    = true;
        $this->ending       = true;
        $this->ended        = true;
    }
}