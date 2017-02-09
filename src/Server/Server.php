<?php

namespace Coff\OneWire\Server;

use Coff\OneWire\Exception\OneWireServerException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

abstract class Server implements ServerInterface, LoggerAwareInterface
{
    protected $sleepTime=100000;
    protected $socket;
    protected $socketName;
    protected $peerTimeout=60;

    protected $lastEveryMinute, $lastEveryNight, $lastEveryHour;

    /* active peer connections */
    protected $connections = array();
    protected $lastConnActive = array();
    protected $connInactivityTimeout=60;

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
            throw new OneWireServerException($msg);
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

    public function setPeerTimeout($secs=60) {
        $this->peerTimeout = $secs;
    }

    public function getConnections() {
        return $this->connections;
    }

    public function closeConnection($id) {

        if (false === isset($this->connections[$id])) {
            return $this;
        }

        if (true === is_resource($this->connections[$id])) {
            fclose($this->connections[$id]);
        }

        unset($this->connections[$id]);
        unset($this->lastConnActive[$id]);

        return $this;
    }

    public function loop() {
        if (!$this->socket) {
            throw new OneWireServerException('There is no socket open for our server instance!');
        }

        while(true) {
            $this->each();

            /* cyclic maintenance tasks */
            list($night, $hour, $minute) = explode(",", date("j,G,i")); /* day, hour, minute */

            if ($night != $this->lastEveryNight) {
                $this->lastEveryNight = $night;

                $this->logger->info('Launching overnight maintenance tasks');
                $this->everyNight();
            }

            if ($hour != $this->lastEveryHour) {
                $this->lastEveryHour = $hour;

                $this->logger->info('Launching hourly maintenance tasks');
                $this->everyHour();
            }

            if ($minute != $this->lastEveryMinute) {
                $this->lastEveryMinute = $minute;

                $this->logger->info('Launching once-a-minute maintenance tasks');
                $this->everyMinute();
            }

            usleep($this->sleepTime);
        }

        fclose($this->socket);

        return $this;
    }

    public function everyMinute() {
    }

    public function everyHour() {
    }

    public function everyNight() {
    }

}
