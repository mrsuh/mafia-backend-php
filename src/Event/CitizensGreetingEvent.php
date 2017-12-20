<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class CitizensGreetingEvent extends Event
{
    const EVENT = 'citizens-greeting';

    const ACTION_ROLE   = 'role';
    const ACTION_ACCEPT = 'accept';

    private $acceptedPlayers;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->acceptedPlayers = [];
        $this->eventPlayers    = $this->players->getAll();
        $this->actions         = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_ACCEPT];
    }

    public function process()
    {
        $count = count($this->players->getAll());

        $roles = $this->getRoles($count);

        shuffle($roles);

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->setRole(array_pop($roles));
            $gamePlayer->sendMessage([
                'status' => 'ok',
                'event'  => static::EVENT,
                'action' => static::ACTION_ROLE,
                'role'   => $gamePlayer->getRole()
            ]);
        }

        $this->processing = true;
    }

    public function isProcessed()
    {
        $this->processed = count($this->eventPlayers) === count($this->acceptedPlayers);

        return $this->processed;
    }

    public function acceptAction(Player $player)
    {
        if (in_array($player, $this->acceptedPlayers)) {
            return false;
        }

        $this->acceptedPlayers[] = $player;

        return true;
    }

    public function action(string $action, Player $player, array $msg = [])
    {
        switch ($action) {
            case static::ACTION_STARTED:
                $this->startedAction($player);
                break;
            case static::ACTION_ENDED:
                $this->endedAction($player);
                break;
            case static::ACTION_ACCEPT:
                $this->acceptAction($player);
                break;
        }

        return true;
    }

    private function getRoles(int $count)
    {
        $mafia   = 0;
        $girl    = 0;
        $sheriff = 0;
        $doctor  = 0;
        $citizen = 0;
        $roles   = [];

        switch (true) {
            case $count >= 5:
                $mafia   = (int)floor($count / 3);
                $girl    = 1;
                $sheriff = 1;
                $doctor  = 1;
                $citizen = $count - ($girl + $sheriff + $doctor + $mafia);
                break;
            case $count === 3:
                $mafia   = 1;
                $doctor  = 1;
                $citizen = 1;
                break;
            case $count === 4:
                $mafia   = 1;
                $girl    = 1;
                $doctor  = 1;
                $citizen = 1;
                break;
        }

        for ($i = 1; $i <= $mafia; $i++) {
            $roles[] = Player::ROLE_MAFIA;
        }

        for ($i = 1; $i <= $citizen; $i++) {
            $roles[] = Player::ROLE_CITIZEN;
        }

        for ($i = 1; $i <= $girl; $i++) {
            $roles[] = Player::ROLE_GIRL;
        }

        for ($i = 1; $i <= $sheriff; $i++) {
            $roles[] = Player::ROLE_SHERIFF;
        }

        for ($i = 1; $i <= $doctor; $i++) {
            $roles[] = Player::ROLE_DOCTOR;
        }

        return $roles;
    }
}