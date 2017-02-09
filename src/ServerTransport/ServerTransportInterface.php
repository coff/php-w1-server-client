<?php

namespace Coff\OneWire\ServerTransport;

/**
 * Interface ServerTransportInterface
 *
 * @package Coff\OneWire\ServerTransport
 */
interface ServerTransportInterface
{
    /**
     * @param string $request
     *
     * @return $this
     */
    public function parseRequest($request);

    /**
     * Returns server response but it can also return error if needed.
     *
     * @return string
     */
    public function getResponse();

    /**
     * Returns low level error response in cases when error is detected
     * on server level (not transport-level).
     * @param $errorMessage
     * @param $code
     *
     * @return string
     */
    public function getErrorResponse($errorMessage, $code=0);
}
