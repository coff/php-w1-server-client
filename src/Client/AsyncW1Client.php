<?php

namespace OneWire\Client;

use OneWire\Exception\OneWireException;
use Psr\Log\LoggerAwareTrait;

class AsyncW1Client extends W1Client
{
    use LoggerAwareTrait;

    const
        STATE_OFFLINE = 0,
        STATE_IDLE = 1,
        STATE_CONN = 1,
        STATE_REPLY_AWAIT = 2,
        STATE_REPLY_TIMEOUT = 3,
        STATE_CONN_TIMEOUT = 4;

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

    public function init()
    {
        parent::init();

        if ($this->isConnected()) {
            $this->logger->info('Connected successfully to socket',
                array('socket'=>$this->socketName));

            $this->state = self::STATE_CONN;
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
            $this->state = self::STATE_CONN_TIMEOUT;
            $this->logger->warning('Connection timeout', array());
            return false;
        }

        fwrite($this->socket, $this->transport->getQuery());

        $this->state = self::STATE_REPLY_AWAIT;
        return true;
    }

    protected function awaitReply() {

        $gotReply = stream_select($a = array($this->socket), $w=null, $o=null, 0, $this->awaitSocket);

        if (false === $gotReply) {

            if (time()-$this->lastRequestTimestamp > $this->awaitTimeout) {
                $this->logger->warning('Reply timeouted',array());
                $this->state = self::STATE_REPLY_TIMEOUT;
            }

            return false;
        }

        $this->failedRequests = 0;

        $data = '';
        while (!feof($this->socket)) {
            $data.= fread($this->socket, 1024);
        }

        $this->transport->parseReply($data);

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
                }
                $this->awaitReply();
                break;

            case self::STATE_REPLY_AWAIT:
                $this->awaitReply();
                break;

            case self::STATE_REPLY_TIMEOUT:
                if ($this->failedRequests > $this->maxFailedRequests) {
                    fclose($this->socket);
                    $this->state = self::STATE_CONN_TIMEOUT;
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
