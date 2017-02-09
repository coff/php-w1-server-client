<?php

namespace Coff\OneWire\DataSource;

use Coff\DataSource\AsyncDataSource;

abstract class W1DataSource extends AsyncDataSource
{
    protected $id;

    public function getId() {
        return $this->id;
    }

    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    public function setStamp($timestamp) {
        $this->stamp = $timestamp;

        return $this;
    }

}
