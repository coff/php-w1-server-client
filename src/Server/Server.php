<?php

namespace OneWire\Server;

use OneWire\Exception\ServerException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

abstract class Server implements ServerInterface, LoggerAwareInterface
{
    protected $sleepTime=100000;
    protected $socket;
    protected $socketName;
    protected $connections;

    /** @var  LoggerInterface $logger */
    protected $logger;

    public function __construct($socketName)
    {
        $this->socketName = $socketName;
        $this->logger = new NullLogger();
    }

    public function init() {
        $this->socket = stream_socket_server($this->socketName, $errorNo, $errStr);

        if (false === $this->socket) {
            $this->logger->log(LogLevel::CRITICAL, $msg = 'Failed to open socket ' . $this->socketName);
            throw new ServerException($msg);
        }

    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function setSleepTime($uSecs=100000)
    {
        $this->sleepTime = $uSecs;

        return $this;
    }

    public function loop() {
        if (!$this->socket) {
            throw new ServerException('There is no socket open for our server instance!');
        }

        while(true) {
            $this->each();
            usleep($this->sleepTime);
        }

        fclose($this->socket);

        return $this;
    }


}
