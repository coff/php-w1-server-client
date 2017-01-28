<?php

namespace OneWire\DataSource;

use OneWire\Client\W1Client;

class W1ServerDataSource extends DataSource
{
    protected $id;

    /**
     * @var W1Client
     */
    protected $client;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function setClient(W1Client $client) {
        $this->client = $client;
    }

    public function update() {
        $this->client->update();
    }
}
