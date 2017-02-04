<?php
namespace OneWire\DataSource;


use OneWire\Exception\DataSourceException;

interface DataSourceInterface
{
    /**
     * Performs any initial actions if needed
     *
     * @return mixed
     */
    public function init();

    /**
     * Performs actions to update value stored within object
     * @return $this
     */
    public function update();

    /**
     * Returns value
     */
    public function getValue();

    /**
     * Returns timestamp for value. Use this to check if value is too old
     * already.
     *
     * @return int
     */
    public function getStamp();

    /**
     * Resets error state to no error
     *
     * @return $this
     */
    public function resetErrorState();

    /**
     * Sets error state and exception
     * @param DataSourceException $exception
     *
     * @return $this
     */
    public function setErrorState(DataSourceException $exception);

    /**
     * Returns true if DataSource is in error state
     *
     * @return boolean
     */
    public function isErrorState();


    /**
     * Returns error message
     *
     * @return string
     */
    public function getException();
}
