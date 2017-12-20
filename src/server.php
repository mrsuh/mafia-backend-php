<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Game;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/Event/Event.php';

$dirs = [
    dirname(__DIR__) . '/src',
    dirname(__DIR__) . '/src/Event',
    dirname(__DIR__) . '/src/Game',
    dirname(__DIR__) . '/src/History',
    dirname(__DIR__) . '/src/Player',
];
foreach ($dirs as $dir) {
    foreach (scandir($dir) as $filename) {
        $path = $dir . '/' . $filename;
        if (is_file($path)) {
            require_once $path;
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Game()
        )
    ),
    8080
);

$server->run();