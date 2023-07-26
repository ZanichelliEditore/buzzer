<?php

namespace App\Models;

/**
 * Class Message represents the message to send.
 * @package App\Models
 *
 */
class Message
{
    private $body;

    public function __construct($body)
    {
        $this->body = $body;
    }
    
    /**
     * @return string|array
     */
    public function getBody()
    {
        return $this->body;
    }
}