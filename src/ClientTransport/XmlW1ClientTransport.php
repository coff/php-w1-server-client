<?php

namespace OneWire\ClientTransport;

use OneWire\DataSource\W1ServerDataSource;
use OneWire\Exception\TransportException;

class XmlW1ClientTransport extends W1ClientTransport
{
    public function getQuery()
    {
        $request = simplexml_load_string('<Request/>');

        /** @var W1ServerDataSource $dataSource */
        foreach ($this->dataSources as $id => $dataSource) {
            $dataSourceReq = $request->addChild('DataSource');
            $dataSourceReq->addAttribute('id', $id);
        }

        $request->addAttribute('autoDiscovery', $this->autoDiscovery ? '1' : '0');

        return $request;
    }

    public function parseReply($reply)
    {
        $reply = simplexml_load_string($reply);

        if (false === $reply) {
            throw new TransportException('Unable to parse reply!');
        }

        foreach ($reply->DataSource as $dataSource) {
            $dataSourceId = (string) $dataSource->id;

            if (false === isset ($this->dataSources[$dataSourceId])) {
                if (true === $this->autoDiscovery) {

                } else {
                    continue;
                }
            }

            if ($dataSource->Error !== null) {

            }
        }
    }
}
