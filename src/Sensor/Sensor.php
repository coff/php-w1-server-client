<?php

namespace OneWire\Sensor;


use OneWire\DataSource\DataSource;

abstract class Sensor implements SensorInterface
{
    protected $measureUnit;
    protected $dataSource;

    protected $measureSuffix;
    protected $description;

    public function getMeasureUnit()
    {
        return $this->measureUnit;
    }

    public function getMeasureSuffix()
    {
        return $this->measureSuffix;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDataSource(DataSource $dataSource) {
        $this->dataSource = $dataSource;

        return $this;
    }

    public function getDataSource() {
        return $this->dataSource;
    }
}
