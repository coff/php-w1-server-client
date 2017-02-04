<?php

namespace OneWire\Server;

use OneWire\DataSource\W1DataSource;
use OneWire\Exception\DataSourceException;
use OneWire\Exception\OneWireServerException;
use OneWire\ServerTransport\ServerTransportInterface;
use OneWire\ServerTransport\W1ServerTransport;
use Psr\Log\LogLevel;

/**
 * W1Server - one wire protocol dataSources server
 *
 * Reading from 1-wire dataSources usually takes some time so this server takes
 * care of doing the dirty stuff for you.
 */
class W1Server extends Server
{
    const
        READING_OUTDATED_MIN = 3, // seconds
        DISCOVERY_TIMEOUT = 60; // seconds


    /**
     * @var W1DataSource[] $dataSources
     */
    protected $dataSources;

    /**
     * @var resource[] $dataSourceStreams
     */
    protected $dataSourceStreams;


    protected $queriedIds;
    protected $provideDiscovered=false;

    /**
     * @var int
     */
    protected $lastQueryTime;

    /**
     * @var int
     */
    protected $lastDiscoveryTime;

    /**
     * @var W1ServerTransport
     */
    protected $transport;

    /**
     * @var bool
     */
    protected $allCollected=false;

    /**
     * @var int $peerTimeout server peer timeout in seconds
     */
    protected $peerTimeout=1;

    public function setTransport(ServerTransportInterface $transport) {
        $this->transport = $transport;
    }

    public function init() {
        parent::init();
        $this->devicesDiscovery();
    }

    protected function devicesDiscovery() {
        if (!$this->transport instanceof W1ServerTransport) {
            throw new OneWireServerException('Transport not set!');
        }

        $dir = new \DirectoryIterator('/sys/devices/w1_bus_master1');

        /* reset streams in case there were some unfinished queries */
        $this->dataSourceStreams = array();

        foreach($dir as $fileInfo) {
            try {
                if ($fileInfo->isDot()) {
                    continue;
                }

                if (false === file_exists($dataSourcePath = $fileInfo->getPathname() . '/w1_slave')) {
                    continue;
                }

                if (false === isset($this->dataSources[$fileInfo->getFilename()])) {
                    $this->dataSources[$fileInfo->getFilename()] = $ds = new W1DataSource($dataSourcePath);
                    $this->responder->addDataSource($ds);
                }

            } catch (DataSourceException $e) {
                $this->logger->log(LogLevel::WARNING, 'Device discovery failed for ' . $fileInfo->getPathname() );
            }
        }

        $this->logger->info('Finished 1-Wire device discovery', array_keys($this->dataSources));

        $this->lastDiscoveryTime = time();
    }

    protected function queryDevices() {
        $this->dataSourceStreams = array();

        /**
         * @var string $key
         * @var W1DataSource $dataSource
         */
        foreach ($this->dataSources as $key => $dataSource) {
            try {
                $stream = $dataSource
                    ->request()
                    ->getStream();

                $this->dataSourceStreams[$key] = $stream;
            } catch (DataSourceException $e) {
                $this->logger->log(LogLevel::ALERT, $e->getMessage(), $e->getCode());
            }
        }
        $this->logger->info('Initialized data source queries', array_keys($this->dataSources));

        $this->lastQueryTime = time();
    }

    protected function readReadings($streams) {
        foreach ($streams as $key => $stream) {
            $dataSource = $this->dataSources[$key];
            $dataSource
                ->update();

            $this->logger->info('Got answer from ' . $key, array());

            if (false === is_resource($stream)) {
                unset($this->dataSourceStreams[$key]);

                if (!$this->dataSourceStreams) {
                    $this->allCollected = true;
                }
            }

        }
    }

    public function each() {

        /**
         * Perform dataSource discovery each at self::DISCOVERY_TIMEOUT
         */
        if ($this->lastDiscoveryTime < time()-self::DISCOVERY_TIMEOUT && true === $this->allCollected) {
            $this->devicesDiscovery(); // performs discovery
        }
        echo '.';
        /**
         * Query dataSources' readings each self::READING_OUTDATED_MIN
         */
        if ($this->lastQueryTime < time()-self::READING_OUTDATED_MIN) {
            $this->queryDevices();
        }

        /**
         * Got any reply from opened processes?
         */
        if ($this->dataSourceStreams && 0 < stream_select($streams = $this->dataSourceStreams, $w=null, $o=null, 0, $this->sleepTime)) {
            $this->readReadings($streams);
        }

        /**
         * Or any incoming client connection?
         */
        if (0 < stream_select($sockets = array($this->socket), $w=null, $o=null, 0, $this->sleepTime)) {
            try {

                $connection = stream_socket_accept($this->socket, $this->peerTimeout, $peerName = '');
                $this->logger->debug('Accepted connection from peer ' . $peerName);

                $dataQueryString = fread($connection, 2048);

                $this->transport
                    ->setDataSources($this->dataSources)
                    ->parse($dataQueryString);

                fwrite($connection, $this->transport->getResponse());
                fclose($connection);

                $this->logger->debug('Response sent');

            } catch (\Exception $e) {
                if (isset($connection) && is_resource($connection)) {
                    // low level error response here
                    fwrite($connection, $this->transport->getErrorResponse($e->getMessage()));
                }
                $this->logger->log(LogLevel::ERROR, 'Peer error: ' . $e->getMessage());
            }
        }

    }
}
