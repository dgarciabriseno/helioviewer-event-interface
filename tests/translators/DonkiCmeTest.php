<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

use function HelioviewerEventInterface\DonkiCme\GetGifsFromDonkiWebPage;

include_once __DIR__ . "/../../src/Translator/DonkiCme.php";

final class DonkiCmeTest extends TestCase
{
    public function testDataWithNoLatLong(): void
    {
        $start = new DateTimeImmutable("2021-12-08T09:01:31Z");
        $end = new DateInterval("P1D");

        $result = Events::GetFromSource(["DONKI"], $start, $end);
        $this->assertNotNull($result);
    }

    public function testGetGifs(): void {
        $gifs = GetGifsFromDonkiWebPage("https://kauai.ccmc.gsfc.nasa.gov/DONKI/view/WSA-ENLIL/24907/1");
        $this->assertCount(16, $gifs);
        foreach ($gifs as $gif) {
            $this->assertStringEndsWith(".gif", $gif);
        }
    }
}

