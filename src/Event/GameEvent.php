<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class GameEvent extends Event
{
    const EVENT = 'game';

    const ACTION_CREATE  = 'create';
    const ACTION_JOIN    = 'join';
    const ACTION_PLAYERS = 'players';
    const ACTION_START   = 'start';
    const ACTION_STOP    = 'stop';

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->eventPlayers = $this->players->getAll();
        $this->actions      = [static::ACTION_CREATE, static::ACTION_JOIN, static::ACTION_ENDED, static::ACTION_START, static::ACTION_STOP];

        $this->starting = true;
        $this->started  = true;
    }

    /**
     * @param string $action
     * @param Player $player
     * @param array  $msg
     */
    public function action(string $action, Player $player, array $msg = [])
    {
        switch ($action) {
            case static::ACTION_CREATE:
                $this->createAction($player, $msg);
                break;
            case static::ACTION_JOIN:
                $this->joinAction($player, $msg);
                break;
            case static::ACTION_START:
                $this->startAction($player, $msg);
                break;
            case static::ACTION_STOP:
                $this->stopAction($player, $msg);
                break;
            case static::ACTION_ENDED:
                $this->endedAction($player);
                break;
        }
    }

    public function createAction(Player $player, array $msg = [])
    {
        $player->setUsername($msg['username']);
        $player->setMaster(true);
        $this->players->add($player);

        $player->sendMessage([
            'event'   => static::EVENT,
            'action'  => static::ACTION_CREATE,
            'game_id' => $this->gameId,
            'player'  => [
                'username' => $player->getUsername(),
                'id'       => $player->getId()
            ]
        ]);

        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            $players[] =
                [
                    'username' => $gamePlayer->getUsername(),
                    'id'       => $gamePlayer->getId()
                ];
        }

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->sendMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_PLAYERS,
                'players' => $players
            ]);
        }
    }

    public function joinAction(Player $player, array $msg = [])
    {
        $username = $msg['username'];
        if (null !== $this->players->getOneByUsername($username)) {

            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_JOIN,
                'message' => 'Username already exists'
            ]);

            return false;
        }

        $player->setUsername($username);
        $this->players->add($player);

        $player->sendMessage([
            'event'  => static::EVENT,
            'action' => static::ACTION_JOIN,
            'player' => [
                'username' => $player->getUsername(),
                'id'       => $player->getId()
            ]]);

        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            $players[] =
                [
                    'username' => $gamePlayer->getUsername(),
                    'id'       => $gamePlayer->getId()
                ];
        }

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->sendMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_PLAYERS,
                'players' => $players
            ]);
        }
    }

    public function startAction(Player $player, array $msg = [])
    {
        if (!$player->isMaster()) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_START,
                'message' => 'access denied'
            ]);

            return false;
        }

        if (count($this->players->getAll()) < 3) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_START,
                'message' => 'too few players'
            ]);

            return false;
        }

        $this->processed = true;
    }

    public function stopAction(Player $player, array $msg = [])
    {
        //todo
    }
}