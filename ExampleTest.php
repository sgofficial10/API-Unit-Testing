<?php


use PHPUnit\Framework\TestCase;
//use Mockery\Adapter\Phpunit\MockeryTestCase;
class ExampleTest extends  TestCase {

    // public function tearDown(): void{
    //     Mockery::close();
    // }

    public function testAddingTwoPlusTwoResultsInFour(){
        $this->assertEquals(4, 2 + 2);
    }




    
}