<?php

namespace OneWire\ServerTransport;

use OneWire\Exception\TransportException;

class XmlW1ServerTransport extends W1ServerTransport
{
    protected $queriedIds;
    protected $isGlobalQuery;
    protected $provideDiscovered;
    protected $refId;

    public function __construct()
    {

    }



    public function parseRequest($request) {

        $request = simplexml_load_string($request);

        if (false === $request) {
            throw new TransportException('Unable to parse request!');
        }


        if ($request->attributes()->autoDiscovery == "1") {
            $this->provideDiscovered = true;
        }

        $this->queriedIds = [];
        $this->isGlobalQuery = false;

        /** @var \SimpleXMLElement $dataSource */
        foreach ($request->DataSource as $dataSource) {
            $this->queriedIds[(string)$dataSource->id] = $dataSource->id;
        }

        if (!$this->queriedIds) {
            $this->queriedIds = array_keys($this->dataSources);
            $this->isGlobalQuery = true;
        }
    }

    public function getResponse() {
        $response = simplexml_load_string('<Response/>');
        foreach ($this->queriedIds as $dataSourceId) {

            $dataSourceResp = $response->addChild('DataSource');
            $dataSourceResp->addAttribute('id', $dataSourceId);

            if (false === isset($this->dataSources[$dataSourceId]) && false === $this->isGlobalQuery) {
                $errorResp = $dataSourceResp->addChild('Error', 'dataSource not found');
                $errorResp->addAttribute('code', 1);
                $errorResp->addAttribute('id', $dataSourceId);
                continue;
            }

            $dataSource = $this->dataSources[$dataSourceId];

            if (true === $dataSource->isErrorState()) {
                $e = $dataSource->getException();
                $errorResp = $dataSourceResp->addChild('Error', $e->getMessage());
                $errorResp->addAttribute('code', $e->getCode() );
                $errorResp->addAttribute('id', $dataSourceId);
                continue;

            }

            $readingResp = $dataSourceResp->addChild('Reading', $dataSource->getValue());
            $readingResp->addAttribute('stamp', $dataSource->getStamp());
        }
    }

    public function getErrorResponse($errorMessage, $code=0)
    {
        return "<?xml version=\"1.0\"?>\n<Response><Error code=\"0\">" . $errorMessage . "</Error></Response>";
    }
}
