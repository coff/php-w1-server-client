<?php

namespace Coff\OneWire\Client;

use Coff\OneWire\Exception\OneWireException;

class AsyncW1Client extends W1Client
{

    const
        STATE_OFFLINE = 'offline',
        STATE_IDLE = 'idle',
        STATE_CONN = 'idle',
        STATE_REPLY_AWAIT = 'await',
        STATE_REPLY_TIMEOUT = 'reply timeout',
        STATE_CONN_TIMEOUT = 'connection timeout';

    protected $state;

    /** @var  int $awaitTimeout seconds to wait for reply */
    protected $awaitTimeout=5;

    /** @var  int $awaitSocket usecs for socket_select to wait */
    protected $awaitSocket=100000;


    protected $minRequestDelay = 3; // seconds
    protected $lastRequestTimestamp;
    protected $minConnAttmDelay = 3;
    protected $lastConnAttmTimestamp;

    protected $failedRequests = 0;
    protected $maxFailedRequests = 3;

    protected $failedConnAttm = 0;
    protected $maxFailedConnAttm = 10;

    public function __construct($socketName='tcp://0.0.0.0:8000')
    {
        parent::__construct($socketName);
        $this->state = self::STATE_OFFLINE;
    }

    protected function setState($state) {
        $this->state = $state;
        $this->logger->debug('In state ' . $this->state );
    }

    public function init()
    {
        parent::init();

        stream_set_blocking($this->socket, false);

        if ($this->isConnected()) {
            $this->setState(self::STATE_CONN);
            $this->failedConnAttm = 0;
        } else {
            $this->logger->error('Failed connecting to socket',
                array('socket'=>$this->socketName, 'attempt'=> $this->failedConnAttm));

            $this->lastConnAttmTimestamp = time();
        }
    }

    protected function request()
    {
        $this->lastRequestTimestamp=time();

        if (false === $this->isConnected()) {
            $this->setState(self::STATE_CONN_TIMEOUT);
            $this->logger->warning('Connection timeout', array());
            return false;
        }

        fwrite($this->socket,
            $this->transport
                ->setClient($this)
                ->getQuery());

        $this->logger->debug('Request sent');

        $this->setState(self::STATE_REPLY_AWAIT);
        return true;
    }

    protected function awaitReply() {

        $gotReply = stream_select($a = array($this->socket), $w=null, $o=null, 0, $this->awaitSocket);

        if (0 === $gotReply) {

            if (time()-$this->lastRequestTimestamp > $this->awaitTimeout) {
                $this->logger->warning('Reply timeouted', array());
                $this->setState(self::STATE_REPLY_TIMEOUT);
            }

            return false;
        }

        $this->failedRequests = 0;

        $dataString = '';
        while ($data = fread($this->socket, 2048)) {
            $dataString.= $data;
        }

        if ($dataString == '' && true === feof($this->socket)) {
            # server disconnected us?
            $this->logger->warning('Server terminated connection. Aborting request.');
            $this->setState(self::STATE_OFFLINE);

            return false;
        }

        $this->transport->parseReply($dataString);
        $this->setState(self::STATE_IDLE);

        return true;
    }

    public function update() {

        switch ($this->state) {
            case self::STATE_OFFLINE:
                $this->init();
                $this->request();
                $this->awaitReply();
                break;

            case self::STATE_IDLE:
                if (time()-$this->lastRequestTimestamp > $this->minRequestDelay) {
                    $this->request();
                    $this->awaitReply();
                }
                break;

            case self::STATE_REPLY_AWAIT:
                $this->awaitReply();
                break;

            case self::STATE_REPLY_TIMEOUT:
                if ($this->failedRequests > $this->maxFailedRequests) {
                    fclose($this->socket);
                    $this->setState(self::STATE_CONN_TIMEOUT);
                }
                $this->failedRequests++;
                $this->request();
                $this->awaitReply();
                break;

            case self::STATE_CONN_TIMEOUT:
                if ($this->failedConnAttm >  $this->maxFailedConnAttm) {
                    throw new OneWireException('Permanent connection failure!');
                }
                if (time()-$this->lastConnAttmTimestamp > $this->minConnAttmDelay) {
                    $this->failedConnAttm++;
                    $this->init();
                    if ($this->isConnected()) {
                        $this->request();
                    }
                    $this->awaitReply();

                }
                break;
        }

    }

    public function getState() {
        return $this->state;
    }
}
