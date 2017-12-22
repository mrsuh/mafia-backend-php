<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class GirlEvent extends Event
{
    const EVENT = 'girl';

    const ACTION_CHOICE  = 'choice';
    const ACTION_PLAYERS = 'players';

    private $choice;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->choice       = null;
        $this->eventPlayers = $this->players->getAll();
        $this->actions      = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_CHOICE];
        $girl               = $this->players->getOneByRole(Player::ROLE_GIRL);

        $this->started   = null === $girl;
        $this->processed = null === $girl;
        $this->ended     = null === $girl;

        $hasGirl = null !== $girl;

        $this->started    = !$hasGirl;
        $this->starting   = !$hasGirl;
        $this->processing = !$hasGirl;
        $this->processed  = !$hasGirl;
        $this->ended      = !$hasGirl;
        $this->ending     = !$hasGirl;
    }

    public function process()
    {
        $girl = $this->players->getOneByRole(Player::ROLE_GIRL);

        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            $players[] = [
                'id'       => $gamePlayer->getId(),
                'username' => $gamePlayer->getUsername()
            ];
        }

        $girl->sendMessage([
            'event'   => static::EVENT,
            'action'  => static::ACTION_PLAYERS,
            'players' => $players
        ]);

        $this->processing = true;

        return true;
    }

    public function choiceAction(Player $player, array $msg)
    {
        if ($player->getRole() !== Player::ROLE_GIRL) {
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