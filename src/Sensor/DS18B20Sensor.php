<?php

namespace OneWire\Sensor;

use OneWire\DataSource\AsyncDataSource;
use OneWire\DataSource\DataSource;

class DS18B20Sensor extends W1Sensor
{
    const
        UNIT_CELSIUS    = 1,
        UNIT_FAHRENHEIT = 2,
        UNIT_KELVIN     = 3;


    /** @var  DataSource */
    protected $dataSource;
    protected $resource;

    /**
     * TemperatureSensor constructor.
     *
     * @param DataSource $dataSource
     * @param int $measureUnit units to display temperature in
     * @param string $measureSuffix
     * @internal param null $device
     */
    public function __construct(DataSource $dataSource, $measureUnit=null, $measureSuffix='°C') {
        $this->measureUnit = $measureUnit;
        $this->measureSuffix = $measureSuffix;
        $this->dataSource = $dataSource;
    }

    protected function parseReading($reading) {

        $lines = explode("\n",$reading);
        $crcLine = trim($lines[0]);
        $valueLine = trim($lines[1]);
        if (substr($crcLine,-1) == 'S') { // YE(S)
            $this->value = (double)explode('=', substr($valueLine,-8))[1] / 1000;
        }
    }

    public function update() {
        $this->parseReading($this->dataSource->getValue());
    }

    public function render() {
        $t = new TemperatureConversion($this->getValue(), new TemperatureUnit(TemperatureUnit::CELSIUS));
        return (string)round($t->to($this->measureUnit),1) . $this->getMeasureSuffix();
    }
}
