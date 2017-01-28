<?php

namespace OneWire\DataSource;

use OneWire\Exception\DataSourceException;

class W1DataSource extends AsyncDataSource
{
    protected $id;
    protected $devicesDir;
    protected $devicePath;

    public function __construct($idOrFullPath, $devicesDir = '/sys/devices/w1_bus_master1')
    {
        $this->devicesDir = $devicesDir;

        if (false === strstr($idOrFullPath, '/')) {
            $this->id = $idOrFullPath;
            $this->devicePath = $this->devicesDir . '/' . $this->id . '/w1_slave';
        } else {
            $idOrFullPath = strtr($idOrFullPath, array('/w1_slave' => ''));
            $this->id = substr(strrchr(rtrim($idOrFullPath, '/'), '/' ),1);
            $this->devicePath = $idOrFullPath . '/w1_slave';
        }
    }

    public function request()
    {
        $this->stream = popen('cat ' . $this->devicePath, 'r');

        if (false === $this->stream) {
            throw new DataSourceException("Couldn't open process stream ", $this->devicePath);
        }

        $res = stream_set_blocking($this->stream, false);
        if (false === $res) {
            throw new DataSourceException("Couldn't set non-blocking mode for stream", $this->devicePath);
        }

        return $this;
    }

    public function update()
    {
        if (false === is_resource($this->stream)) {
            throw new DataSourceException('Stream is not a resource!');
        }

        $s = '';
        while (!feof($this->stream)) {
            $s.= fread($this->stream, 2048);
        }

        $this->value = $s;
        $this->stamp = time();

        pclose($this->stream);

        return $this;
    }

    public function getDevicePath() {
        return $this->devicePath;
    }
}
