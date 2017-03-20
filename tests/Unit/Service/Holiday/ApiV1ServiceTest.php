<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Services\Holiday\ApiV1Service;


class ApiV1ServiceTest extends TestCase
{
    public function testInputValidation()
    {
        $ApiV1Service = new ApiV1Service();

        $this->assertArrayHasKey('status', $ApiV1Service->validateInput([]));
        $this->assertArrayHasKey('error', $ApiV1Service->validateInput([]));

        $this->assertArrayHasKey('status', $ApiV1Service->validateInput(['country'=>'Test']));
        $this->assertArrayHasKey('error', $ApiV1Service->validateInput([['country'=>'Test']]));

        $this->assertEmpty($ApiV1Service->validateInput(['country'=>'Test', 'year'=>2017]));
    }
}
