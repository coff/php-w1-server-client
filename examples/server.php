#!/usr/bin/php
<?php

namespace OneWire;

use Monolog\Formatter\LogglyFormatter;
use OneWire\Server\W1Server;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

include (__DIR__ . '/../vendor/autoload.php');

$s = new W1Server('tcp://0.0.0.0:8000');
$s->setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_VERY_VERBOSE, $isDecorated=true, new OutputFormatter())));
$s->init();

$s->loop();
