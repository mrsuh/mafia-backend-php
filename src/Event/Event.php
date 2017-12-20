<?php

namespace App\Event;

use App\History\History;
use App\Player\Player;
use App\Player\Players;

abstract class Event
{
    const EVENT = 'event';

    const ACTION_START   = 'start';
    const ACTION_STARTED = 'started';
    const ACTION_END     = 'end';
    const ACTION_ENDED   = 'ended';

    /**
     * @var Player[]
     */
    protected $startedPlayers;

    /**
     * @var Player[]
     */
    protected $endedPlayers;

    /**
     * @var Player[]
     */
    protected $eventPlayers;

    /**
     * @var int
     */
    protected $iteration;

    /**
     * @var
     */
    protected $gameId;

    /**
     * @var bool
     */
    protected $processed;
    protected $processing;

    /**
     * @var bool
     */
    protected $started;
    protected $starting;

    /**
     * @var bool
     */
    protected $ended;
    protected $ending;

    /**
     * @var string[]
     */
    protected $actions;

    /**
     * @var History
     */
    protected $history;

    /**
     * @var Players
     */
    protected $players;

    public function __construct(History $history, Players $players, int $gameId, int $iteration = 1)
    {
        $this->history = $history;
        $this->players = $players;

        $this->processed  = false;
        $this->processing = false;
        $this->started    = false;
        $this->starting   = false;
        $this->ended      = false;
        $this->ending     = false;

        $this->iteration = $iteration;
        $this->gameId    = $gameId;

        $this->startedPlayers = [];
        $this->endedPlayers   = [];
        $this->eventPlayers   = [];

        $this->actions = [static::ACTION_STARTED];
    }

    public function getIteration()
    {
        return $this->iteration;
    }

    public function start()
    {
        foreach ($this->players->getAll() as $eventPlayer) {
            $this->startedPlayers[$eventPlayer->getId()] = false;
            $eventPlayer->sendMessage([
                'event'     => static::EVENT,
                'action'    => static::ACTION_START,
                'iteration' => $this->iteration
            ]);
        }

        $this->starting = true;

        return true;
    }

    public function isStarted()
    {
        $started = true;
        foreach ($this->startedPlayers as $playerId => $value) {
            if (!$value) {
                $started = false;
                break;
            }
        }

        return $this->started || $started;
    }

    public function isStarting()
    {
        return $this->starting;
    }

    public function process()
    {
        $this->processing = true;

        return true;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function isProcessing()
    {
        return $this->processing;
    }

    public function end()
    {
        foreach ($this->players->getAll() as $eventPlayer) {
            $this->endedPlayers[$eventPlayer->getId()] = false;
            $eventPlayer->sendMessage([
                'event'  => static::EVENT,
                'action' => static::ACTION_END
            ]);
        }

        $this->ending = true;
    }

    public function isEnded()
    {
        $ended = true;
        foreach ($this->endedPlayers as $playerId => $value) {
            if (!$value) {
                $ended = false;
                break;
            }
        }

        return $this->ended || $ended;
    }

    public function isEnding()
    {
        return $this->ending;
    }

    public function hasAction(string $action)
    {
        return in_array($action, $this->actions);
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
            default:
                return false;
        }

        return true;
    }

    public function startedAction(Player $player)
    {
        if (!array_key_exists($player->getId(), $this->startedPlayers)) {
            return false;
        }

        $this->startedPlayers[$player->getId()] = true;

        return true;
    }

    public function endedAction(Player $player)
    {
        if (!array_key_exists($player->getId(), $this->endedPlayers)) {
            return false;
        }

        $this->endedPlayers[$player->getId()] = true;

        return true;
    }
}