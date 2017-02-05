<?php

namespace OneWire\ClientTransport;


use OneWire\Client\W1Client;
use OneWire\DataSource\DataSource;
use OneWire\DataSource\W1ServerDataSource;

abstract class W1ClientTransport implements ClientTransportInterface
{
    /**
     * @var W1Client
     */
    protected $client;

    /**
     * Callback to call when new DataSource showed up in server response
     *
     * @var callable
     */
    protected $autoDiscoveryCallback;

    /**
     * Sets auto-discovery callback method
     * @param callable|null $callback
     * @return $this
     */
    public function setAutoDiscoveryCallback(callable $callback = null) {
        $this->autoDiscoveryCallback = $callback;

        return $this;
    }

    public function setClient(W1Client $client) {
        $this->client = $client;

        return $this;
    }

    /**
     * @param DataSource[] $dataSources
     *
     * @return $this
     */
    public function setDataSources($dataSources) {
        $this->dataSources = $dataSources;

        return $this;
    }

    public function addDataSource(W1ServerDataSource $dataSource) {
        $this->dataSources[$dataSource->getId()] = $dataSource;
    }
}
