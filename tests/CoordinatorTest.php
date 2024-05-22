<?php declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use HelioviewerEventInterface\Coordinator\Coordinator;
use PHPUnit\Framework\TestCase;

final class CoordinatorTest extends TestCase
{
    public function testHpc(): void
    {
        // Using RHESSI Flare 12070596 as the test subject
        // hpc_x = 515
        // hpc_y = -342
        $coordinate = Coordinator::HPC(515, -342, "2012-07-05 13:01:46");
        $this->assertEqualsWithDelta(523.6178, $coordinate["x"], 0.0001);
        $this->assertEqualsWithDelta(-347.7228, $coordinate["y"], 0.0001);

        // Setting the target time forward should increase the X coordinate
        $coordinate = Coordinator::HPC(515, -342, "2012-07-05 13:01:46", "2012-07-06 00:00:00");
        $this->assertGreaterThan(524, $coordinate["x"]);
    }

    public function testException() {
        $this->expectException(RequestException::class);
        Coordinator::HPC(0, 0, "Invalid Date");
    }
}

