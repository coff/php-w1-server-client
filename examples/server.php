#!/usr/bin/php
<?php

namespace OneWire\Examples;

use OneWire\Server\W1Server;
use OneWire\ServerTransport\XmlW1ServerTransport;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

include (__DIR__ . '/../vendor/autoload.php');

$s = new W1Server('tcp://0.0.0.0:8000');
$s->setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG, $isDecorated=true, new OutputFormatter())));
$s->setTransport(new XmlW1ServerTransport());
$s->init();

$s->loop();
