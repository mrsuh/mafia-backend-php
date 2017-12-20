<?php

namespace App;

use App\Game\Game;

class Control
{
    private $games;

    public function __construct()
    {
        $this->games = [];
    }

    /**
     * @param string $id
     * @return Game|null
     */
    public function findGameById(int $id)
    {
        if (!array_key_exists($id, $this->games)) {
            return null;
        }

        return $this->games[$id];
    }

    public function createGame()
    {
        $game                        = new Game();
        $this->games[$game->getId()] = $game;

        return $game;
    }
}
