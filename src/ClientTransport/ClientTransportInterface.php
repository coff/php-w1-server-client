<?php
namespace Coff\OneWire\ClientTransport;

interface ClientTransportInterface
{
    public function parseReply($reply);

    public function getQuery();
}
