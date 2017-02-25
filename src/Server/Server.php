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

    protected $lastEveryMinute, $lastEveryNight, $lastEveryHour, $lastEverySecond;

    protected $hourCallbacks = array();
    protected $minuteCallbacks = array();
    protected $secondCallbacks = array();

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

        if (substr($this->socketName, 0, 4) === 'unix' &&
            file_exists(substr($this->socketName, 7))) {
            throw new OneWireServerException('Unix socket already opened. Server still running? Otherwise please delete socket file: ' . $this->socketName);
        }

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
            list($night, $hour, $minute, $second) = explode(",", date("j,G,i,s")); /* day, hour, minute */

            if ($night != $this->lastEveryNight) {
                $this->lastEveryNight = $night;

                $this->logger->info('Launching overnight maintenance tasks');
                $this->everyNight();
            }

            if ($hour != $this->lastEveryHour) {
                $this->lastEveryHour = $hour;

                foreach ($this->hourCallbacks as $time => $callback) {
                    if ($hour % $time == 0) {
                        $this->logger->debug('Launching every ' . $time . '-hours callback');
                        call_user_func_array($callback, array());
                    }
                }
            }

            if ($minute != $this->lastEveryMinute) {
                $this->lastEveryMinute = $minute;

                foreach ($this->minuteCallbacks as $time => $callback) {
                    if ($minute % $time == 0) {
                        $this->logger->debug('Launching every ' . $time . '-minutes callback');
                        call_user_func_array($callback, array());
                    }
                }
            }

            if ($second != $this->lastEverySecond) {
                $this->lastEverySecond= $second;

                foreach ($this->secondCallbacks as $time => $callback) {
                    if ($second % $time == 0) {
                        $this->logger->debug('Launching every ' . $time . '-seconds callback');
                        call_user_func_array($callback, array());
                    }
                }
            }

            usleep($this->sleepTime);
        }

        fclose($this->socket);

        return $this;
    }

    /**
     *
     * Remarks:
     * - There's no way to add more than one callback for each timespan
     *
     * @param string $delay delay as in examples: 1s - every second, 2m - two minutes
     * @param callable $callback
     * @return $this
     * @throws OneWireServerException
     */
    public function addShortCycleCallback($delay, callable $callback) {

        switch (substr($delay, -1,1)) {
            case 's':
                $this->secondCallbacks[(int)$delay] = $callback;
                break;
            case 'm':
                $this->minuteCallbacks[(int)$delay] = $callback;
                break;
            case 'h':
                $this->hourCallbacks[(int)$delay] = $callback;
                break;
            default:
                throw new OneWireServerException('Unknown unit!');
        }

        return $this;
    }

    public function everyNight() {
    }

}
