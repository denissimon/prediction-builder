<?php

require_once '../src/PredictionBuilder.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PredictionBuilder\PredictionBuilder;

class PredictionBuilderTest extends TestCase
{
    // 1 ------------------------------------------------------------------------>
    
    public static function resultData() {
        $data = [[1,20],[2,70],[2,45],[3,81],[5,73],[6,80],[7,110]];        
        return [
            [4.5, $data, 76.65],
            [8, $data, 113.27274]
        ];
    }

    #[DataProvider('resultData')]
    public function testGetResult($x, $data, $expected_result) {
        $prediction = new PredictionBuilder($x, $data);
        $result = $prediction->build();
        
        $this->assertEquals($result->y, $expected_result);
    }
    
    // 2 ------------------------------------------------------------------------>
	
    public static function exceptionData() {
        return [
            [4.5, [[1,20],[2,70]]],
            [4.5, [[1,20],[2,70],[2],[3,81],[5,73],[6,80],[7,110]]],
            [4.5, [[1,20],[2,70],[],[3,81],[5,73],[6,80],[7,110]]]
        ];
    }

    #[DataProvider('exceptionData')]
    public function testException($x, $data) {
        $this->expectException(InvalidArgumentException::class);
        
        $prediction = new PredictionBuilder($x, $data);
        $result = $prediction->build();
    }
}
