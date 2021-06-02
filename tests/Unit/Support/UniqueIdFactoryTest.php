<?php


namespace Tests\Unit\Support;


use App\Support\UniqueIdFactory;
use PHPUnit\Framework\TestCase;

class UniqueIdFactoryTest extends TestCase
{
    public function testGeneratingSortableByTimeUniqueId()
    {
        // Setup
        $gen = new UniqueIdFactory();
        $alphabet = range('A', 'E');
        $A = $B = $C = $D = $E = null;

        // Execute
        for ($i = 0; $i < 5; $i++) {
            ${$alphabet[$i]} = $i; // Use varying variables to dynamically set $A = 0, $B = 1, $C = 2, etc.
        }
        $this->assertEquals($A, 0); // Check that uses varying variables
        $this->assertEquals($B, 1);
        $this->assertEquals($C, 2);
        $this->assertEquals($D, 3);
        $this->assertEquals($E, 4);
        for ($i = 0; $i < 5; $i++) {
            ${$alphabet[$i]} = $gen->generateSortableByTimeUniqueId();
            sleep(1); // Sleep so that it has a different timestamp to generate the ID
        }

        // Assert
        $this->assertTrue($A < $B
            && $B < $C
            && $C < $D
            && $D < $E
        );
    }
}
