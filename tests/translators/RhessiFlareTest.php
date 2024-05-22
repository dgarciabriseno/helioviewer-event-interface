<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use HelioviewerEventInterface\Translator\RhessiFlare;

final class RhessiFlareTest extends TestCase
{
    public function testShortLabelDefaultLabel(): void
    {
        $data = explode(",", "2021229,2002-02-12 02:15:24,2002-02-12 02:19:22,2002-02-12 02:25:48,46,75270,958,-118,25-50,100,5,2,2002/02/12/20020212_0215_0225/hsi_20020212_0215_0225.html");

        $rhessi_flare = new RhessiFlare($data);

        $rhessi_event = $rhessi_flare->asEvent();

        $this->assertEquals("2021229: 2002-02-12 02:15:24", $rhessi_event->short_label);
    }

    public function testRhessiFlarePosition() {
        // RHESSI Flare 120705117
        //  This flare is positioned off the solar disk
        //  https://umbra.nascom.nasa.gov/rhessi/rhessi_extras/flare_images_v2/2012/07/05/20120705_0325_0330/hsi_20120705_0325_0330.html
        //  (-883,-348)
        //  2012-07-05 03:29:06
        $data = explode(",", "120705117,2012-07-05 03:25:44,2012-07-05 03:29:06,2012-07-05 03:30:40,271,209865,-883,-348,25-50,1.5209671E+11,4,3,2012/07/05/20120705_0325_0330/hsi_20120705_0325_0330.html");

        $rhessi_flare = new RhessiFlare($data);

        $rhessi_event = $rhessi_flare->asEvent();

        $event_data = [
            'groups' => [
                array('data' => [(array) $rhessi_event])
            ]
        ];

        $event_data = RhessiFlare::Transform($event_data, new DateTime('2012-07-05 03:29:06'));
        $event = $event_data['groups'][0]['data'][0];

        $this->assertEqualsWithDelta(-897.7240, $event['hv_hpc_x'], 0.0001);
        $this->assertEqualsWithDelta(-353.8028, $event['hv_hpc_y'], 0.0001);
    }

    public function testQueryRhessiEvents() {
    }
}

