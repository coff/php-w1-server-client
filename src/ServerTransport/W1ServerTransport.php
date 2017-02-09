<?php

namespace Coff\OneWire\ServerTransport;

use Coff\OneWire\DataSource\W1DataSource;
use Coff\OneWire\Server\W1Server;

abstract class W1ServerTransport implements ServerTransportInterface
{
    /** @var W1Server */
    protected $server;

    public function setServer(W1Server $server) {
        $this->server = $server;

        return $this;
    }

    public function addDataSource(W1DataSource $dataSource) {
        $this->dataSources[$dataSource->getId()] = $dataSource;
    }
}
