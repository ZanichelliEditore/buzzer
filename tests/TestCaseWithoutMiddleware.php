<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

abstract class TestCaseWithoutMiddleware extends BaseTestCase
{
    use CreatesApplication, WithoutMiddleware;
}
