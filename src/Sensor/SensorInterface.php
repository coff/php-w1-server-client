<?php

namespace Coff\OneWire\Sensor;

interface SensorInterface
{
    public function getMeasureUnit();

    public function setDescription($description);

    public function getDescription();

    /**
     * Updates sensor from DataSource
     * @return $this
     */
    public function update();

    /**
     * Returns sensor measure
     * @return double
     */
    public function getValue();
}
