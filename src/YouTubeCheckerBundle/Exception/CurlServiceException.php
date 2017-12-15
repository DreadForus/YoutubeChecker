<?php

namespace YouTubeCheckerBundle\Exception;


class CurlServiceException extends CustomException
{
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}