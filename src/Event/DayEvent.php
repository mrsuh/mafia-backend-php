<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class DayEvent extends Event
{
    const EVENT = 'day';

    const ACTION_OUT = 'out';

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->eventPlayers = $this->players->getAll();

        $this->ending = true;
        $this->ended  = true;
    }

    public function process()
    {
        if (1 === $this->iteration) {

            $this->processing = true;
            $this->processed  = true;

            return true;
        }

        $events = $this->history->findByIteration($this->iteration - 1);

        $mafiaChoice  = null;
        $doctorChoice = null;
        $girlChoice   = null;
        $dead         = null;

        foreach ($events as $event) {
            switch ($event::EVENT) {
                case MafiaEvent::EVENT:
                    $mafiaChoice = $event->getChoice();
                    break;
                case GirlEvent::EVENT:
                    $girlChoice = $event->getChoice();
                    break;
                case DoctorEvent::EVENT:
                    $doctorChoice = $event->getChoice();
                    break;
            }
        }

        if (null !== $mafiaChoice) {
            if (
                (null !== $girlChoice && $girlChoice->getId() === $mafiaChoice->getId()) ||
                (null !== $doctorChoice && $doctorChoice->getId() === $mafiaChoice->getId())
            ) {
                $dead = null;
            } else {
                $dead = $mafiaChoice;
            }
        }

        if (null !== $dead) {
            $player = $this->players->getOneById($dead->getId());

            foreach ($this->players->getAll() as $gamePlayer) {
                $gamePlayer->sendMessage([
                    'status' => 'ok',
                    'event'  => static::EVENT,
                    'action' => static::ACTION_OUT,
                    'player' => [
                        'username' => $player->getUsername(),
                        'id'       => $player->getId()
                    ]
                ]);
            }

            $player->setStatus(Player::STATUS_INACTIVE);
        }

        $this->processing = true;
        $this->processed  = true;

        return true;
    }
}