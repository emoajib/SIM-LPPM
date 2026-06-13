<?php

namespace Tests\Unit;

use App\Services\LetterService;
use PHPUnit\Framework\TestCase;

class LetterServiceTest extends TestCase
{
    public function test_it_can_convert_month_to_roman()
    {
        $service = new LetterService;

        $this->assertEquals('I', $service->getRomanMonth(1));
        $this->assertEquals('V', $service->getRomanMonth(5));
        $this->assertEquals('VI', $service->getRomanMonth(6));
        $this->assertEquals('XII', $service->getRomanMonth(12));
    }
}
