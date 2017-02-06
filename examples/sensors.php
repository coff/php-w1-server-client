#!/usr/bin/php
<?php

namespace OneWire\Examples;

use OneWire\Client\AsyncW1Client;
use OneWire\ClientTransport\XmlW1ClientTransport;
use OneWire\DataSource\W1ServerDataSource;
use OneWire\Sensor\DS18B20Sensor;
use OneWire\Sensor\Sensor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

include (__DIR__ . '/../vendor/autoload.php');

$w1client = new AsyncW1Client('tcp://0.0.0.0:8000');
$w1client->setLogger($logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, $isDecorated=true, new OutputFormatter())));
$sensors = [];

$w1client->setTransport(new XmlW1ClientTransport());
$w1client->setAutoDiscoveryCallback(function($id) use ($logger, &$sensors) {
    $logger->info('Callback has been called with id=' . $id);

    $dataSource = new W1ServerDataSource($id);
    $sensor = new DS18B20Sensor($dataSource);
    $sensors[] = $sensor;
    var_dump($sensors);
    return $dataSource;
});
$w1client->setAutoDiscovery(true);
while (true) {
    $w1client->update();

    /** @var Sensor $sensor */
    foreach ($sensors as $sensor) {
        echo $sensor->update()->getValue()."\t|";
    }
    echo "\r";
    sleep(1);
}


