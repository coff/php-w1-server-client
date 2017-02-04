<?php

namespace OneWire\DataSource;

use OneWire\Exception\DataSourceException;

abstract class W1DataSource extends AsyncDataSource
{
    protected $id;




    public function getId() {
        return $this->id;
    }



}
