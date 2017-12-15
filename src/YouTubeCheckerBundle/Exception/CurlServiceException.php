<?php

namespace YouTubeCheckerBundle\Exception;



use Exception;

class CurlServiceException extends CustomException
{
    public function __construct(string $message, int $code, Exception $exception = null)
    {
        parent::__construct($message, $code, $exception);
    }
}