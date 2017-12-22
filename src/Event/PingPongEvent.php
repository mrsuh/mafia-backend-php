<?php

namespace App\Event;

class PingPongEvent extends Event
{
    const EVENT = 'pingpong';

    const ACTION_PING = 'ping';
    const ACTION_PONG = 'pong';
}