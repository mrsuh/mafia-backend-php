<?php

namespace App\Player;

class Players
{
    /**
     * @var Player[]
     */
    private $players;

    /**
     * @return Player[]
     */
    public function getAll($active = true)
    {
        $players = [];
        foreach ($this->players as $player) {

            if ($active && $player->getStatus() === Player::STATUS_INACTIVE) {
                continue;
            }

            $players[] = $player;
        }

        return $players;
    }

    public function __construct()
    {
        $this->players = [];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function add(Player $player)
    {
        $this->players[] = $player;

        return true;
    }

    /**
     * @param string $id
     * @return null|Player
     */
    public function getOneById(string $id)
    {
        foreach ($this->players as $player) {

            if ($player->getStatus() === Player::STATUS_INACTIVE) {
                continue;
            }

            if ($player->getId() === $id) {

                return $player;
            }
        }

        return null;
    }

    public function getOneByRole(string $role)
    {
        foreach ($this->players as $player) {

            if ($player->getStatus() === Player::STATUS_INACTIVE) {
                continue;
            }

            if ($player->getRole() === $role) {

                return $player;
            }
        }

        return null;
    }

    /**
     * @param string $role
     * @return Player[]
     */
    public function getByRole(string $role)
    {
        $players = [];
        foreach ($this->players as $player) {

            if ($player->getStatus() === Player::STATUS_INACTIVE) {
                continue;
            }

            if ($player->getRole() === $role) {
                $players[] = $player;
            }
        }

        return $players;
    }

    /**
     * @param string $connectionId
     * @return null|Player
     */
    public function getOneByConnectionId(string $connectionId)
    {
        foreach ($this->players as $player) {

            if ((string)$player->getConnectionId() === (string)$connectionId) {

                return $player;
            }
        }

        return null;
    }
}