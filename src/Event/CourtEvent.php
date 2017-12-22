<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class CourtEvent extends Event
{
    const EVENT = 'court';

    const ACTION_PLAYERS = 'players';
    const ACTION_VOTE    = 'vote';
    const ACTION_OUT     = 'out';

    private $votedPlayers;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->votedPlayers = [];
        $this->eventPlayers = $this->players->getAll();
        $this->actions      = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_VOTE];
    }

    public function process()
    {
        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            $players[] = [
                'id'       => $gamePlayer->getId(),
                'username' => $gamePlayer->getUsername()
            ];
        }

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->sendMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_PLAYERS,
                'players' => $players
            ]);
        }

        $this->processing = true;

        return true;
    }

    public function end()
    {
        $voices = [];
        foreach ($this->votedPlayers as $playerId) {
            if (!array_key_exists($playerId, $voices)) {
                $voices[$playerId] = 0;
            }

            $voices[$playerId]++;
        }

        $maxVoices  = max($voices);
        $candidates = [];
        foreach ($voices as $playerId => $voiceWeight) {
            if ($voiceWeight === $maxVoices) {
                $candidates[] = $playerId;
            }
        }

        if (count($candidates) > 1) {

            parent::end();

            return false;
        }

        $deadId = array_pop($candidates);
        $dead   = $this->players->getOneById($deadId);

        if (null === $dead) {

            parent::end();

            return false;
        }

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->sendMessage([
                'event'  => static::EVENT,
                'action' => static::ACTION_OUT,
                'player' => [
                    'username' => $dead->getUsername(),
                    'id'       => $dead->getId()
                ]
            ]);
        }

        parent::end();

        $dead->setStatus(Player::STATUS_INACTIVE);

        return true;
    }

    public function isProcessed()
    {
        $this->processed = count($this->eventPlayers) === count($this->votedPlayers);

        return $this->processed;
    }

    public function voteAction(Player $player, array $msg)
    {
        if (array_key_exists($player->getId(), $this->votedPlayers)) {
            return false;
        }

        $playerId     = $msg['player_id'];
        $playerChoice = $this->players->getOneById($playerId);

        if (null === $playerChoice) {
            $player->sendErrorMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_VOTE,
                'message' => 'invalid player id'
            ]);

            return false;
        }

        foreach ($this->players->getAll() as $gamePlayer) {
            $gamePlayer->sendMessage([
                'event'  => static::EVENT,
                'action' => static::ACTION_VOTE,
                'player' => ['id' => $player->getId(), 'username' => $player->getUsername()],
                'vote'   => ['id' => $playerChoice->getId(), 'username' => $playerChoice->getUsername()]
            ]);
        }

        $this->votedPlayers[$player->getId()] = $playerChoice->getId();

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
            case static::ACTION_VOTE:
                $this->voteAction($player, $msg);
                break;
        }

        return true;
    }
}