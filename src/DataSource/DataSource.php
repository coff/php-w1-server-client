<?php

namespace OneWire\DataSource;

use OneWire\Exception\DataSourceException;

abstract class DataSource implements DataSourceInterface
{
    protected $value;
    protected $stamp;
    protected $exception;
    protected $errorState=false;

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

    /**
     * Resets errorState status
     * @return $this
     */
    public function resetErrorState() {
        $this->errorState = false;
        $this->exception = null;

        return $this;
    }

    /**
     * Sets error state and exception to throw eventually
     * @param DataSourceException $exception
     *
     * @return $this
     */
    public function setErrorState(DataSourceException $exception)
    {
        $this->errorState = true;
        $this->exception = $exception;

        return $this;
    }

    /**
     * Returns errorState status
     * @return bool
     */
    public function isErrorState()
    {
        return $this->errorState;
    }

    /**
     * Returns Exception if in error state
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->errorState ? $this->exception : null;
    }
}
