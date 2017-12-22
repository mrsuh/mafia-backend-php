<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class DoctorEvent extends Event
{
    const EVENT = 'doctor';

    const ACTION_CHOICE  = 'choice';
    const ACTION_PLAYERS = 'players';

    private $choice;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->choice       = null;
        $this->eventPlayers = $this->players->getAll();
        $this->actions      = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_CHOICE];
        $doctor             = $this->players->getOneByRole(Player::ROLE_DOCTOR);

        $hasDoctor = null !== $doctor;

        $this->started    = !$hasDoctor;
        $this->starting   = !$hasDoctor;
        $this->processing = !$hasDoctor;
        $this->processed  = !$hasDoctor;
        $this->ended      = !$hasDoctor;
        $this->ending     = !$hasDoctor;
    }

    public function process()
    {
        $doc = $this->players->getOneByRole(Player::ROLE_DOCTOR);

        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            $players[] = [
                'id'       => $gamePlayer->getId(),
                'username' => $gamePlayer->getUsername()
            ];
        }

        $doc->sendMessage([
            'event'   => static::EVENT,
            'action'  => static::ACTION_PLAYERS,
            'players' => $players
        ]);

        $this->processing = true;

        return true;
    }

    public function choiceAction(Player $player, array $msg)
    {
        if ($player->getRole() !== Player::ROLE_DOCTOR) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_CHOICE,
                'message' => 'access denied'
            ]);

            return false;
        }

        $playerId     = $msg['player_id'];
        $playerChoice = $this->players->getOneById($playerId);

        if (null === $playerChoice) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_CHOICE,
                'message' => 'invalid player id'
            ]);

            return false;
        }

        $prevEvent = $this->history->findOneByTypeAndIteration(static::EVENT, $this->iteration - 1);

        if (
            null !== $prevEvent &&
            null !== $prevEvent->getChoice() &&
            $prevEvent->getChoice()->getId() === $playerChoice->getId()
        ) {
            $playerChoice->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_CHOICE,
                'message' => 'double check'
            ]);

            $this->processing = false;

            return false;
        }

        $this->processed = true;
        $this->choice    = $playerChoice;
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
            case static::ACTION_CHOICE:
                $this->choiceAction($player, $msg);
                break;
        }

        return true;
    }

    public function getChoice()
    {
        return $this->choice;
    }
}