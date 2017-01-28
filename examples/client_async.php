#!/usr/bin/php
<?php

$w1client = new \OneWire\Client\AsyncW1Client();

while (true) {
    $w1client->update();
    sleep(1);
}

