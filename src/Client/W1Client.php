<?php

namespace OneWire\Client;

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
    protected $dataSources;

    protected $socket;
    protected $socketName;
    protected $autoDiscovery;

    public function __construct($socketName='tcp://0.0.0.0:8000', $autoDiscovery = false)
    {
        $this->socketName = $socketName;
        $this->autoDiscovery = $autoDiscovery;
    }

    public function createDataSourceById($id) {
        $this->dataSources[$id] = new W1ServerDataSource($id);
        return $this->dataSources[$id];
    }

    public function getDataSourceById($id) {
        if (false === isset($this->dataSources[$id])) {
              throw new OneWireException('Not found source ' . $id);
        }

        return $this->dataSources[$id];
    }

    public function init() {
        $this->socket = stream_socket_client($this->socketName);
    }

    public function isConnected() {
        return is_resource($this->socket);
    }

    /**
     * Builds request document
     * @return \SimpleXMLElement request XML document
     */
    protected function buildRequest() {
        $request = simplexml_load_string('<Request/>');
        return $request;
    }

    /**
     * Interprets reply document
     * @param string $reply
     */
    protected function analyzeReply($reply) {
        $simpleReply = simplexml_load_string($reply);

        /** @var \SimpleXMLElement $child */
        foreach ($simpleReply->children() as $child) {
            $id = $child->id;

        }
    }

    public function update() {
        fwrite($this->socket, $this->buildRequest()->asXML());
        $data = '';
        while (!feof($this->socket)) {
            $data.= fread($this->socket, 1024);
        }

        $this->analyzeReply($data);
    }
}
