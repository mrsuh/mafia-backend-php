<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class SheriffEvent extends Event
{
    const EVENT = 'sheriff';

    const ACTION_CHOICE      = 'choice';
    const ACTION_CHOICE_DONE = 'choice-done';
    const ACTION_PLAYERS     = 'players';

    private $choice;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->choice       = null;
        $this->eventPlayers = $this->players->getAll();
        $this->actions      = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_CHOICE, static::ACTION_CHOICE_DONE];
        $sheriff            = $this->players->getOneByRole(Player::ROLE_SHERIFF);

        $hasSheriff = null !== $sheriff;

        $this->started    = !$hasSheriff;
        $this->starting   = !$hasSheriff;
        $this->processing = !$hasSheriff;
        $this->processed  = !$hasSheriff;
        $this->ended      = !$hasSheriff;
        $this->ending     = !$hasSheriff;
    }

    public function process()
    {
        $girl = $this->players->getOneByRole(Player::ROLE_SHERIFF);

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
        if ($player->getRole() !== Player::ROLE_SHERIFF) {
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

        $this->choice = $playerChoice;

        $player->sendMessage([
            'event'  => static::EVENT,
            'action' => static::ACTION_CHOICE,
            'player' => [
                'id'       => $playerChoice->getId(),
                'username' => $playerChoice->getUsername(),
                'role'     => $playerChoice->getRole()
            ]
        ]);

        return true;
    }

    public function choiceDoneAction(Player $player)
    {
        if ($player->getRole() !== Player::ROLE_SHERIFF) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_CHOICE,
                'message' => 'access denied'
            ]);

            return false;
        }

        $this->processed = true;

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
            case static::ACTION_CHOICE:
                $this->choiceAction($player, $msg);
                break;
            case static::ACTION_CHOICE_DONE:
                $this->choiceDoneAction($player);
                break;
        }

        return true;
    }

    public function getChoice()
    {
        return $this->choice;
    }
}