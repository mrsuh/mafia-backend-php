<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class MafiaEvent extends Event
{
    const EVENT = 'mafia';

    const ACTION_VOTE    = 'vote';
    const ACTION_PLAYERS = 'players';

    private $votedPlayers;
    private $choice;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->votedPlayers = [];
        $this->choice       = null;
        $this->eventPlayers = $this->players->getByRole(Player::ROLE_MAFIA);
        $this->actions      = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_VOTE];
    }

    public function process()
    {
        $players = [];
        foreach ($this->players->getAll() as $gamePlayer) {
            if ($gamePlayer->getRole() === Player::ROLE_MAFIA) {
                continue;
            }

            $players[] = [
                'id'       => $gamePlayer->getId(),
                'username' => $gamePlayer->getUsername()
            ];
        }

        foreach ($this->eventPlayers as $eventPlayer) {
            $eventPlayer->sendMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_PLAYERS,
                'players' => $players
            ]);
        }

        $this->processing = true;

        return true;
    }

    public function isProcessed()
    {
        $this->processed = count($this->eventPlayers) === count($this->votedPlayers);

        return $this->processed;
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

        $this->choice = $dead;

        parent::end();

        return true;
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

    public function getChoice()
    {
        return $this->choice;
    }
}