<?php

namespace MyApp;

use App\Control;
use App\Event\Event;
use App\Event\GameEvent;
use App\Event\PingPongEvent;
use App\Player\Player;
use App\Player\Players;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Game implements MessageComponentInterface
{

    private $control;

    public function __construct()
    {
        $this->control = new Control();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
    }

    private function getGame(ConnectionInterface $conn, int $gameId, string $event, string $action)
    {
        $game = $this->control->findGameById($gameId);

        if (GameEvent::ACTION_CREATE === $action && null !== $game) {
            $conn->send([
                'status'  => 'error',
                'message' => 'game already exists'
            ]);

            return null;
        }

        if (null !== $game) {

            return $game;
        }

        if (GameEvent::EVENT !== $event) {
            $conn->send([
                'status'  => 'error',
                'message' => 'invalid event'
            ]);

            return null;
        }

        if (GameEvent::ACTION_CREATE !== $action) {
            $conn->send([
                'status'  => 'error',
                'message' => 'invalid action'
            ]);

            return null;
        }

        echo 'NEW GAME' . PHP_EOL;

        return $this->control->createGame();
    }

    private function getPlayer(ConnectionInterface $conn, string $gamerId, Players $players)
    {
        if (null === $player = $players->getOneById($gamerId)) {
            echo 'NEW PLAYER' . PHP_EOL;
            $player = new Player($conn);
        }

        if ($player->getConnectionId() !== $conn->resourceId) {
            $player->setConnection($conn);
        }

        return $player;
    }

    public function onMessage(ConnectionInterface $from, $msgRaw)
    {
        $msg = json_decode($msgRaw, true);

        if (!is_array($msg)) {
            $from->send(json_encode([
                'status'  => 'error',
                'message' => 'message has invalid json'
            ]));

            return false;
        }

        echo $msgRaw . PHP_EOL;

        foreach (['game_id', 'gamer_id', 'event', 'action'] as $key) {
            if (!array_key_exists($key, $msg)) {
                $from->send(json_encode([
                    'status'  => 'error',
                    'message' => sprintf('has not key %s', $key)
                ]));

                return false;
            }
        }

        $gameId       = (int)$msg['game_id'];
        $gamerId      = (string)$msg['gamer_id'];
        $eventString  = (string)$msg['event'];
        $actionString = (string)$msg['action'];

        if ($eventString === PingPongEvent::EVENT && $actionString === PingPongEvent::ACTION_PING) {
            $from->send(json_encode([
                'status' => 'ok',
                'event'  => PingPongEvent::EVENT,
                'action' => PingPongEvent::ACTION_PONG
            ]));

            return false;
        }

        $game = $this->getGame($from, $gameId, $eventString, $actionString);

        if (null === $game) {
            $from->send(json_encode([
                'status'  => 'error',
                'message' => sprintf('there is no game with id %s', $gameId)
            ]));

            return false;
        }

        $player = $this->getPlayer($from, $gamerId, $game->getPlayers());

        $currentEvent = $game->getEventFactory()->getCurrent();


        if ($player->getStatus() === Player::STATUS_INACTIVE && $actionString !== Event::ACTION_ENDED) {

            return false;
        }

        echo 'CURRENT EVENT ' . $currentEvent::EVENT . PHP_EOL;

        if ($currentEvent::EVENT !== $eventString) {
            $from->send(json_encode([
                'status'  => 'error',
                'message' => 'invalid event'
            ]));

            return false;
        }

        if (!$currentEvent->hasAction($actionString)) {
            $from->send(json_encode([
                'status'  => 'error',
                'message' => 'invalid action'
            ]));

            return false;
        }

        $currentEvent->action($actionString, $player, $msg);
        $event = $game->getEventFactory()->getCurrent();
        while (true) {
            if (null !== $game->getWinner()) {

                break;
            }

            if ($game->isEnded()) {
                echo 'GAME OVER' . PHP_EOL;

                foreach ($game->getPlayers()->getAll(false) as $gamePlayer) {
                    $gamePlayer->sendMessage([
                        'event'  => 'game',
                        'action' => 'over',
                        'winner' => $game->getWinner()
                    ]);
                }

                break;
            }
            echo $event::EVENT . ' IS STARTING' . PHP_EOL;
            if (!$event->isStarting()) {
                $event->start();
            }

            if (!$event->isStarted()) {
                break;
            }
            echo 'EVENT ' . $event::EVENT . ' is started' . PHP_EOL;

            echo $event::EVENT . ' IS PROCESSING' . PHP_EOL;
            if (!$event->isProcessing()) {
                $event->process();
            }

            if (!$event->isProcessed()) {
                break;
            }
            echo 'EVENT ' . $event::EVENT . ' is processed' . PHP_EOL;

            echo $event::EVENT . ' IS ENDING' . PHP_EOL;
            if (!$event->isEnding()) {
                $event->end();
            }

            if (!$event->isEnded()) {
                break;
            }
            echo 'EVENT ' . $event::EVENT . ' is ended' . PHP_EOL;

            $event = $game->getEventFactory()->nextEvent();

            if (null === $event) {
                break;
            }

            echo 'NEW EVENT ' . $event::EVENT . PHP_EOL;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
//        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
