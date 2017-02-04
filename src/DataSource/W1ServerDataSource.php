<?php

namespace OneWire\DataSource;

use OneWire\Client\W1Client;

class W1ServerDataSource extends W1DataSource
{

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

        return $this;
    }

    public function request()
    {
        // what to implement here?

        return $this;
    }

    public function setValue($value) {
        $this->value;

        return $this;
    }

    public function update() {
        $this->client->update();
    }
}
