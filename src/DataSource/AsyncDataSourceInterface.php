<?php

namespace OneWire\DataSource;

interface AsyncDataSourceInterface extends DataSourceInterface
{
    public function getStream();

    public function request();

    public function awaitReply();

    public function setAwaitTime($uSecs);

}
