<?php
namespace OneWire\DataSource;


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
}
