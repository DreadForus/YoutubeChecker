<?php

namespace YouTubeCheckerBundle\Exception;


class ResponseCurlServiceException extends CurlServiceException
{

    /**
     * ResponseCurlServiceException constructor.
     */
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}