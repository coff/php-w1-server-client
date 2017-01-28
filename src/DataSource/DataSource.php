<?php

namespace OneWire\DataSource;

abstract class DataSource implements DataSourceInterface
{
    protected $value;
    protected $stamp;

    public function init()
    {
        // default behavior is to do nothing here

        return $this;
    }

    /**
     * Returns value read via update() method
     */
    public function getValue() {

        return $this->value;
    }


    public function getStamp()
    {
        return $this->stamp;
    }
}
