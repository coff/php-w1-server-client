<?php

namespace Coff\OneWire\Sensor;

use Coff\DataSource\DataSource;

abstract class Sensor implements SensorInterface
{
    protected $measureUnit;

    protected $dataSource;

    protected $description;

    protected $value;

    public function getMeasureUnit()
    {
        return $this->measureUnit;
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

    public function getValue()
    {
        return $this->value;
    }
}
