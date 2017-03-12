<?php

namespace Coff\OneWire\Sensor;

use Coff\DataSource\DataSource;
use Coff\DataSource\DataSourceInterface;

/**
 * Sensor
 *
 * Sensor's abstract class.
 */
abstract class Sensor implements SensorInterface, DataSourceInterface
{
    /**
     * Measure unit. Hm...
     *
     * @var string
     */
    protected $measureUnit;

    /**
     * An object that reads source data for that sensor.
     *
     * @var DataSource
     */
    protected $dataSource;

    /**
     * Sensor's descriptive text.
     *
     * @var
     */
    protected $description;

    /**
     * Sensor reading value.
     *
     * @var
     */
    protected $value;

    /**
     * Sensor value linear correction
     *
     * @var
     */
    protected $correction=0;

    /**
     * Returns sensor's measure unit
     * @return mixed
     */
    public function getMeasureUnit()
    {
        return $this->measureUnit;
    }

    /**
     * Sets sensor's descriptive text
     *
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Returns sensor's descriptive text
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets datasource.
     *
     * @param DataSource $dataSource
     * @return $this
     */
    public function setDataSource(DataSource $dataSource) {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * Returns datasource.
     *
     * @return DataSource
     */
    public function getDataSource() {
        return $this->dataSource;
    }

    /**
     * Sets linear correction for sensor
     *
     * @param double $correction
     * @return $this
     */
    public function setCorrection($correction)
    {
        $this->correction = $correction;

        return $this;
    }

    /**
     * Returns linear correction set for this sensor (correction is also
     * applied in the value automatically).
     *
     * @return mixed
     */
    public function getCorrection()
    {
        return $this->correction;
    }

    /**
     * Returns reading value for sensor.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value + $this->correction;
    }

    /**
     * Returns timestamp for for this sensor's DataSource reading.
     * @return int
     */
    public function getStamp()
    {
        return $this->getDataSource()->getStamp();
    }
}
