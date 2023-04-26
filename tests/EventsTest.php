<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    private DateTime $START_DATE;
    private DateInterval $LENGTH;
    public function __construct() {
        parent::__construct("EventsTest");
        $this->START_DATE = new DateTime('2023-04-01');
        $this->LENGTH = new DateInterval('P1D');
    }

    protected function setUp(): void
    {
        Cache::Clear();
    }

    /**
     * Verifies that events can be queried and that the closure to modify individual records is working
     */
    public function testGetEvents(): void
    {
        $result = Events::GetFromSource(["DONKI"], $this->START_DATE, $this->LENGTH, function ($record) {$record->hv_hpc_x = 999; return $record;});
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertTrue(array_key_exists('groups', $result[0]));
        // Verify closure works
        $this->assertEquals(999, $result[0]['groups'][0]['data'][0]['hv_hpc_x']);
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetNothingFromSource(): void {
        $emptySet = Events::GetFromSource(["beep beep"], $this->START_DATE, $this->LENGTH);
        $this->assertIsArray($emptySet);
        $this->assertEmpty($emptySet);
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetFromSource(): void {
        $data = Events::GetFromSource(["CCMC"], $this->START_DATE, $this->LENGTH);
        $this->assertTrue(is_array($data));
        $this->assertEquals(2, count($data));
        $this->assertTrue(array_key_exists('groups', $data[0]));
        $this->assertEquals(8, count($data[0]['groups'][0]['data']));
    }

    public function testGetAll(): void {
        $length = new DateInterval("P2D");
        $start = new DateTime();
        $start->sub($length);
        Events::GetAll($start, $length);
    }

    /**
     * Tests that the cache lock mechanism to prevent multiple requests is working.
     */
    public function testCacheLock(): void {
        // Create a temporary directory which will be used to track execution of parallel code
        $tmpdir = sys_get_temp_dir() . "/phpunit_test";
        if (is_dir($tmpdir)) {
            exec("rm -r " . $tmpdir);
        }
        mkdir($tmpdir);

        // Fork the process so that we can simulate parallel requests
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->assertTrue(false, "Fork failed");
        } else if ($pid) {
            // Parent process. When postprocessor is executed, create a file in the temp directory.
            Events::GetFromSource(["DONKI"], $this->START_DATE, $this->LENGTH, function ($e) use ($tmpdir) {
                touch($tmpdir . "/parent");
                return $e;
            });
            error_log("Finished parent\n");
        } else {
            // Child process. This should run in parallel with the parent process.
            // When the post processor runs, create a temp file in the temp directory
            Events::GetFromSource(["DONKI"], $this->START_DATE, $this->LENGTH, function ($e) use ($tmpdir) {
                touch($tmpdir . "/child");
                return $e;
            });
            error_log("Finished child\n");
            // End the child process here.
            exit;
        }

        // Now if the lock is working, then only one file will be created.
        // This is because one of the above functions will race to get the cache lock.
        // Once one has the lock, the other will not perform any HTTP request or event processing.
        // It will wait for the value to be computed and stored in the cache, then return the cached value.
        // Only one will perform the request and postprocessing.
        $files = scandir($tmpdir);
        // Remove the linux . and .. from the list
        $files = array_diff($files, ['.', '..']);
        $this->assertCount(1, $files);
    }
}

