<?php

namespace Coff\OneWire\ClientTransport;

use Coff\OneWire\DataSource\W1ServerDataSource;
use Coff\OneWire\Exception\TransportException;

class XmlW1ClientTransport extends W1ClientTransport
{
    public function getQuery()
    {
        $request = simplexml_load_string('<Request/>');

        /** @var W1ServerDataSource $dataSource */
        foreach ($this->client->getDataSources() as $id => $dataSource) {
            $dataSourceReq = $request->addChild('DataSource');
            $dataSourceReq->addAttribute('id', $id);
        }

        $request->addAttribute('autoDiscovery', $this->client->getAutoDiscovery());

        return $request->asXML();
    }

    public function parseReply($reply)
    {
        $reply = simplexml_load_string($reply);
        if (false === $reply) {
            throw new TransportException('Unable to parse reply!');
        }

        foreach ($reply->DataSource as $dataSourceEntity) {
            $dataSourceId = (string) $dataSourceEntity->attributes()->id;
            $dataSource = $this->client->getDataSourceByIdWithAutoDiscovery($dataSourceId);

            /* if auto-discovery failed, we just skip it */
            if (false === $dataSource) {
                continue;
            }
            if (isset($dataSourceEntity->Error)) {
                $dataSource->setErrorState(
                    new DataSourceException((string)$dataSourceEntity->Error,
                        (int)$dataSourceEntity->attributes()->code));
                continue;
            }
            if (isset($dataSourceEntity->Reading)) {
                $dataSource
                    ->setValue((string)$dataSourceEntity->Reading)
                    ->setStamp((int)$dataSourceEntity->attributes()->stamp);
            }
        }
    }
}
