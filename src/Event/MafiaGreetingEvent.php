<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

class MafiaGreetingEvent extends Event
{
    const EVENT = 'mafia-greeting';

    const ACTION_PLAYERS = 'players';
    const ACTION_ACCEPT  = 'accept';

    private $acceptedPlayers;

    public function __construct(History $history, Players $players, $gameId, $iteration = 1)
    {
        parent::__construct($history, $players, $gameId, $iteration);

        $this->acceptedPlayers = [];
        $this->actions         = [static::ACTION_STARTED, static::ACTION_ENDED, static::ACTION_ACCEPT];
    }

    public function process()
    {
        $players = [];
        foreach ($this->players->getByRole(Player::ROLE_MAFIA) as $eventPlayer) {
            $players[] = [
                'id'       => $eventPlayer->getId(),
                'username' => $eventPlayer->getUsername()
            ];
        }
        foreach ($this->players->getByRole(Player::ROLE_MAFIA) as $eventPlayer) {
            $this->acceptedPlayers[$eventPlayer->getId()] = false;
            $eventPlayer->sendMessage([
                'event'   => static::EVENT,
                'action'  => static::ACTION_PLAYERS,
                'players' => $players
            ]);
        }

        $this->processing = true;
    }

    public function isProcessed()
    {
        $ended = true;
        foreach ($this->acceptedPlayers as $playerId => $value) {
            if (!$value) {
                $ended = false;
                break;
            }
        }

        $this->processed = $ended;

        return $this->processed;
    }

    public function acceptAction(Player $player)
    {
        if (!array_key_exists($player->getId(), $this->acceptedPlayers)) {
            return false;
        }

        $this->acceptedPlayers[$player->getId()] = true;

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
}