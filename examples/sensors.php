#!/usr/bin/php
<?php

namespace OneWire\Examples;

use Coff\OneWire\Client\AsyncW1Client;
use Coff\OneWire\ClientTransport\XmlW1ClientTransport;
use Coff\OneWire\DataSource\W1ServerDataSource;
use Coff\OneWire\Sensor\DS18B20Sensor;
use Coff\OneWire\Sensor\Sensor;
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
    switch ($id) {
        case '28-0000084a49a8': $sensor->setDescription('Source In '); break;
        case '28-0000084b947a': $sensor->setDescription('Buffer Ret'); break;
        case '28-00000891595f': $sensor->setDescription('Heater Pwr'); break;
        case '28-0000088fc71c': $sensor->setDescription('Heater Ret'); break;
        case '28-0416747d17ff': $sensor->setDescription('Source Out'); break;
    }

    $sensors[] = $sensor;
    return $dataSource;
});
$w1client->setAutoDiscovery(true);
while (true) {
    $w1client->update();

    /** @var Sensor $sensor */
    foreach ($sensors as $sensor) {
        echo $sensor->getDescription().':'.sprintf("%.1f", $sensor->update()->getValue())."\t|";
    }
    echo "\r";
    sleep(1);
}


