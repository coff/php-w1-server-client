<?php

namespace OneWire\Client;

use OneWire\ClientTransport\W1ClientTransport;
use OneWire\DataSource\W1DataSource;
use OneWire\DataSource\W1ServerDataSource;
use OneWire\Exception\OneWireClientException;
use Psr\Log\LoggerAwareTrait;

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
    use LoggerAwareTrait;

    /**
     * @var W1ServerDataSource[]
     */
    protected $dataSources = [];

    protected $ignoredIds;

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
        return $this->dataSources[$id];
    }

    /**
     * Returns DataSource by ID, if DataSource doesn't exist yet an autoDiscovery
     * callback is launched with an ID as parameter and callback's result handles
     * three possible results:
     * - false is returned so we add this DataSource to ignored
     * - DataSource is returned so that it is added to dataSources array
     * - otherwise we create proper DataSource
     * @param $id
     *
     * @return W1DataSource|boolean
     */
    public function getDataSourceByIdWithAutoDiscovery($id) {

        if (isset($this->dataSources[$id])) {
            return $this->dataSources[$id];
        }

        /* no auto-discovery - just return false */
        if (false === $this->autoDiscovery) {
            return false;
        }

        if (false === is_callable($this->autoDiscoveryCallback)) {
            return $this->dataSources[$id] = new W1ServerDataSource($id);
        }

        $res = call_user_func_array($this->autoDiscoveryCallback, array($id));

        if (false === $res) {
            $this->setIgnored($id);
            return false;
        }

        if ($res instanceof W1DataSource) {
            $this->dataSources[$res->getId()] = $res;
            return $res;
        }

        return $this->dataSources[$id] = new W1ServerDataSource($id);
    }

    public function getDataSourceById($id) {
        if (false === isset($this->dataSources[$id])) {
              throw new OneWireClientException('Not found source ' . $id);
        }

        return $this->dataSources[$id];
    }

    public function getDataSources() {
        return $this->dataSources;
    }

    public function setIgnored($dataSourceId) {
        $this->ignoredIds[$dataSourceId] = $dataSourceId;

        return $this;
    }

    public function isIgnored($dataSourceId) {
        return isset($this->ignoredIds[$dataSourceId]);
    }

    /**
     * Sets auto-discovery state
     * @param $state
     *
     * @return $this
     */
    public function setAutoDiscovery($state) {
        $this->autoDiscovery = (bool) $state;

        return $this;
    }

    public function getAutoDiscovery() {
        return $this->autoDiscovery;
    }

    /**
     * Sets auto-discovery callback method
     * @param callable|null $callback
     * @return $this
     */
    public function setAutoDiscoveryCallback(callable $callback = null) {
        $this->autoDiscoveryCallback = $callback;

        return $this;
    }

    public function init() {
        $this->socket = stream_socket_client($this->socketName);
        $this->logger->info('Connected successfully to socket',
            array('socket'=>$this->socketName));
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

        $this->transport
            ->setClient($this)
            ->parseReply($data);
    }
}
