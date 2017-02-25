#!/usr/bin/php
<?php

namespace OneWire\Examples;

use Coff\OneWire\Server\W1Server;
use Coff\OneWire\ServerTransport\XmlW1ServerTransport;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

include (__DIR__ . '/../vendor/autoload.php');

$s = new W1Server('unix://server.socket');
$s->setLogger(new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG, $isDecorated=true, new OutputFormatter())));
$s->setTransport(new XmlW1ServerTransport());
$s->init();

$s->loop();
