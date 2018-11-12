<?php

namespace Coff\OneWire\Server;

use Coff\DataSource\Exception\DataSourceException;
use Coff\OneWire\DataSource\W1DataSource;
use Coff\OneWire\DataSource\W1FileDataSource;
use Coff\OneWire\Exception\OneWireServerException;
use Coff\OneWire\ServerTransport\ServerTransportInterface;
use Coff\OneWire\ServerTransport\W1ServerTransport;
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
        READINGS_TIMEOUT = 10, // seconds
        DISCOVERY_TIMEOUT = 60, // seconds
        DEADCONN_TIMEOUT = 120; // seconds


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

        $this->addShortCycleCallback(self::DISCOVERY_TIMEOUT . 's', [$this, 'devicesDiscovery']);
        $this->addShortCycleCallback(self::READINGS_TIMEOUT . 's', [$this, 'queryDevices']);
        $this->addShortCycleCallback(self::DEADCONN_TIMEOUT . 's', [$this, 'terminateDeadConnections']);

        $this->devicesDiscovery();
    }

    public function getDataSources() {
        return $this->dataSources;
    }

    public function getDataSourceById($id) {
        return isset($this->dataSources[$id]) ? $this->dataSources[$id] : false;
    }

    public function devicesDiscovery() {
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
                    $this->dataSources[$fileInfo->getFilename()] = $ds = new W1FileDataSource($dataSourcePath);
                }

            } catch (DataSourceException $e) {
                $this->logger->log(LogLevel::WARNING, 'Device discovery failed for ' . $fileInfo->getPathname() );
            }
        }

        $this->logger->info('Finished 1-Wire device discovery', array_keys($this->dataSources));

        $this->lastDiscoveryTime = time();
    }

    public function queryDevices() {
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

    public function handleConnections() {
        $result = stream_select($connections = $this->connections, $w=null, $o=null, 0, $this->sleepTime);

        if (false === $result) {
            return false;
        }

        /* none of connected peers has sent us data */
        if (0 == $result) {
            return null;
        }

        foreach ($connections as $key => $connection) {
            try {

                /* peer has closed connection */
                if (true === feof($connection)) {
                    $this->logger->debug('Peer has closed connection ' . $key);
                    fclose($connection);
                    unset($this->connections[$key]);
                    continue;
                }

                $this->logger->debug('Got request from peer ' . $key);

                $dataString = '';
                while ($data = fread($connection, 2048)) {
                    $dataString.= $data;
                }

                /* just in case */
                if ($dataString == '')
                    continue;

                $this->lastConnActive[$key] = time();

                $this->transport
                    ->setServer($this)
                    ->parseRequest($dataString);

                fwrite($connection, $this->transport->getResponse());

                $this->logger->debug('Response sent');

            } catch (\Exception $e) {
                if (isset($connection) && is_resource($connection)) {
                    // low level error response here
                    fwrite($connection, $this->transport->getErrorResponse($e->getMessage()));
                    $this->closeConnection($key);
                }
                $this->logger->log(LogLevel::ERROR, 'Peer error: ' . $e->getMessage());
            }
        }
    }

    public function each() {

        /**
         * Got any reply from opened processes?
         */
        $streams = $this->dataSourceStreams; $w=null; $o=null;

        if ($this->dataSourceStreams && 0 < stream_select($streams, $w, $o, 0, $this->sleepTime)) {
            $this->readReadings($streams);
        }

        /**
         * Or any incoming client connection?
         */
        $sockets = array($this->socket); $w=null; $o=null;

        if (0 < stream_select($sockets, $w, $o, 0, $this->sleepTime)) {
            $this->connections[] = $connection = stream_socket_accept($this->socket, $this->peerTimeout, $peerName = '');
            stream_set_blocking($connection, false);
            $this->logger->debug('Accepted connection from peer ');
        }

        /*
         * Having connections? Check if some client asks for something
         */
        if ($this->connections) {
            $this->handleConnections();
        }
    }

    public function terminateDeadConnections() {
        foreach ($this->connections as $index => $connection) {

            if (time()-$this->lastConnActive[$index] < $this->connInactivityTimeout) {
                continue;
            }

            $this->logger->debug('Closing connection ' . $index);
            fclose($connection);
            unset($this->connections[$index]);
        }
    }

}
