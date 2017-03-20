<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HolidayControllerTest extends TestCase
{
    public function testHolidayApi()
    {
        $this->visit('/api/v1/holidays?country=US&year=2017')->assertResponseStatus(200);
        $this->visit('/api/v1/holidays?country=US&year=2017&bankHolidays=1')->assertResponseStatus(200);
        $this->visit('/api/v1/holidays?country=US&year=2017&month=1&bankHolidays=1')->assertResponseStatus(200);
    }
}
