<?php

namespace OneWire\ServerTransport;

use OneWire\DataSource\W1DataSource;

abstract class W1ServerTransport implements ServerTransportInterface
{
    /** @var  W1DataSource[] */
    protected $dataSources;

    /**
     * @param $dataSources
     *
     * @return $this
     */
    public function setDataSources($dataSources) {
        $this->dataSources = $dataSources;

        return $this;
    }

    public function addDataSource(W1DataSource $dataSource) {
        $this->dataSources[$dataSource->getId()] = $dataSource;
    }
}
