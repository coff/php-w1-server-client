<?php

namespace OneWire\Sensor;

interface SensorInterface
{
    /**
     * Renders values in proper units with measuring suffix.
     * @return $this
     */
    public function render();

    public function getMeasureUnit();

    public function getMeasureSuffix();

    public function setDescription($description);

    public function getDescription();
}
