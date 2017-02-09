<?php

namespace Coff\OneWire\Server;

interface ServerInterface
{
    /**
     * ServerInterface constructor. Creates socket for server.
     *
     * @param string $socketName name of a socket to create
     */
    public function __construct($socketName);

    /**
     * Initialization method called before main loop()
     * Please consider it can be called multiple times within the loop too.
     * @return mixed
     */
    public function init();

    /**
     * Sets sleep time so that main loop() won't use your whole CPU power.
     * @param $uSecs
     * @return $this
     */
    public function setSleepTime($uSecs=100000);

    /**
     * Main server loop. Should iterate over each() method.
     * @return $this
     */
    public function loop();

    /**
     * One iteration of server loop.
     * @return mixed
     */
    public function each();
}
