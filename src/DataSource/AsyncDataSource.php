<?php

namespace OneWire\DataSource;

abstract class AsyncDataSource extends DataSource implements AsyncDataSourceInterface
{
    protected $stream;
    protected $awaitTime;

    public function getStream()
    {
        return $this->stream;
    }

    public function setAwaitTime($uSecs)
    {
        $this->awaitTime = $uSecs;

        return $this;
    }

    public function awaitReply()
    {
        $res = stream_select($a=array($this->stream), $w=null, $o=null, 0, $this->awaitTime);
        if (false === $res) {
            return false;
        }

        if ($a) {
            $this->update();
        }

        return null;
    }

}
