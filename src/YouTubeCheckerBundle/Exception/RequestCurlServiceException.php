<?php

namespace YouTubeCheckerBundle\Exception;


use Exception;

class RequestCurlServiceException extends CurlServiceException
{
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}