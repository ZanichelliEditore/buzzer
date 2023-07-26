<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class DuplicateEntryException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Duplicate entry", Response::HTTP_CONFLICT);
    }
}
