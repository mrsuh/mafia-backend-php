<?php

namespace App\Player;

use Ratchet\ConnectionInterface;

class Player
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    const ROLE_CITIZEN = 'ROLE_CITIZEN';
    const ROLE_MAFIA   = 'ROLE_MAFIA';
    const ROLE_DOCTOR  = 'ROLE_DOCTOR';
    const ROLE_GIRL    = 'ROLE_GIRL';
    const ROLE_SHERIFF = 'ROLE_SHERIFF';

    private $status;
    private $id;
    private $username;
    private $token;
    private $role;
    private $master;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection, string $username = '')
    {
        $this->connection = $connection;
        $this->master     = false;
        $this->username   = $username;
        $this->id         = str_replace('.', '', uniqid('', true));
        $this->status     = self::STATUS_ACTIVE;
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function setMaster(bool $master)
    {
        $this->master = $master;
    }

    public function isMaster()
    {
        return $this->master;
    }

    public function sendMessage(array $msg)
    {
        $msg['status'] = 'ok';

        return $this->connection->send(json_encode($msg));
    }

    public function sendErrorMessage(array $msg)
    {
        $msg['status'] = 'error';

        return $this->connection->send(json_encode($msg));
    }

    public function getConnectionId()
    {
        return $this->connection->resourceId;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param $role
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }
}