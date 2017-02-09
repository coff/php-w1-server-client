<?php

namespace Coff\OneWire\Sensor;

use Coff\DataSource\DataSource;

class DS18B20Sensor extends W1Sensor
{

    /** @var  DataSource */
    protected $dataSource;
    protected $resource;

    /**
     * TemperatureSensor constructor.
     *
     * @param DataSource $dataSource
     * @param string $measureUnit
     */
    public function __construct(DataSource $dataSource, $measureUnit = 'celsius') {
        $this->dataSource = $dataSource;
        $this->measureUnit = $measureUnit;
    }

    protected function parseReading($reading) {
        $lines = explode("\n", $reading);
        $crcLine = trim($lines[0]);
        $valueLine = trim($lines[1]);
        if (substr($crcLine,-1) == 'S') { // YE(S)
            $this->value = (double)explode('=', substr($valueLine,-8))[1] / 1000;
        }
    }

    public function update() {
        $this->parseReading($this->dataSource->update()->getValue());

        return $this;
    }
}
