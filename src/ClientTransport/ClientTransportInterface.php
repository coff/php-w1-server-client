<?php
namespace OneWire\ClientTransport;

interface ClientTransportInterface
{
    public function parseReply($reply);

    public function getQuery();
}
