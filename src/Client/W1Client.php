<?php

namespace OneWire\Client;

use OneWire\ClientTransport\W1ClientTransport;
use OneWire\DataSource\DataSource;
use OneWire\DataSource\W1ServerDataSource;
use OneWire\Exception\OneWireException;

/**
 * W1Client
 *
 *
 * $w1 = new W1Client();
 *
 * $w1->update();
 *
 */
class W1Client
{
    /**
     * @var W1ServerDataSource[]
     */
    protected $dataSources;

    protected $socket;
    protected $socketName;
    protected $autoDiscovery=false;
    protected $autoDiscoveryCallback;

    /**
     * Transport layer object
     *
     * @var W1ClientTransport
     */
    protected $transport;

    public function __construct($socketName='tcp://0.0.0.0:8000')
    {
        $this->socketName = $socketName;
    }

    /**
     * Sets Transport layer object for client
     *
     * @param W1ClientTransport $transport
     *
     * @return $this
     */
    public function setTransport(W1ClientTransport $transport) {
        $this->transport = $transport;

        return $this;
    }

    public function createDataSourceById($id) {
        $this->dataSources[$id] = new W1ServerDataSource($id);
        $this->transport->addDataSource($this->dataSources[$id]);
        return $this->dataSources[$id];
    }

    public function getDataSourceById($id) {
        if (false === isset($this->dataSources[$id])) {
              throw new OneWireException('Not found source ' . $id);
        }

        return $this->dataSources[$id];
    }

    public function setAutoDiscoveryCallback(callable $callback = null) {
        $this->autoDiscoveryCallback = $callback;
        if ($callback === null) {
            $this->autoDiscovery = false;
        } else {
            $this->autoDiscovery = true;
        }

        return $this;
    }

    public function init() {
        $this->socket = stream_socket_client($this->socketName);
    }

    public function isConnected() {
        return is_resource($this->socket);
    }

    public function update() {
        fwrite($this->socket, $this->transport->getQuery());
        $data = '';
        while (!feof($this->socket)) {
            $data.= fread($this->socket, 1024);
        }

        $this->transport->parseReply($data);
    }
}
