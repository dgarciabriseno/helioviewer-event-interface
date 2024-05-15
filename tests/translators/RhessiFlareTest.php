<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use HelioviewerEventInterface\Translator\RhessiFlare;

final class RhessiFlareTest extends TestCase
{
    public function testShortLabelDefaultLabel(): void 
    {
        $data = explode(",", "2021229,2002-02-12 02:15:24,2002-02-12 02:19:22,2002-02-12 02:25:48,46,75270,958,-118,25-50,5,2,2002/02/12/20020212_0215_0225/hsi_20020212_0215_0225.html");

        $rhessi_flare = new RhessiFlare($data);

        $rhessi_event = $rhessi_flare->asEvent();

        $this->assertEquals("2002-02-12 02:15:24", $rhessi_event->short_label);
    }

}

