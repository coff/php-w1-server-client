#!/usr/bin/php
<?php

namespace OneWire\Examples;

use OneWire\Client\AsyncW1Client;
use OneWire\ClientTransport\XmlW1ClientTransport;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

include (__DIR__ . '/../vendor/autoload.php');

$w1client = new AsyncW1Client('tcp://0.0.0.0:8000');
$w1client->setLogger($logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG, $isDecorated=true, new OutputFormatter())));

$w1client->setTransport(new XmlW1ClientTransport());
$w1client->setAutoDiscoveryCallback(function($id) use ($logger) {
    $logger->info('Callback has been called with id=' . $id);
    return true;
});
$w1client->setAutoDiscovery(true);

while (true) {
    $w1client->update();
    usleep(1000000);

}


