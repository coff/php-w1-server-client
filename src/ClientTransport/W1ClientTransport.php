<?php

namespace OneWire\ClientTransport;


use OneWire\DataSource\DataSource;
use OneWire\DataSource\W1ServerDataSource;

abstract class W1ClientTransport implements ClientTransportInterface
{
    protected $autoDiscovery;

    /** @var  DataSource[] */
    protected $dataSources;

    /**
     * Sets auto-discovery mode
     * @param bool $state
     * @return $this
     */
    public function setAutoDiscovery($state) {
        $this->autoDiscovery = $state;

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
