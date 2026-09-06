<?php

namespace Tests\Feature\Commercial;

use App\Services\Edge\HardwareFingerprintCollector;
use Tests\TestCase;

class HardwareFingerprintTest extends TestCase
{
    private HardwareFingerprintCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new HardwareFingerprintCollector;
    }

    public function test_same_host_fingerprint_collection_is_stable_and_deterministic(): void
    {
        $first = $this->collector->collect();
        $second = $this->collector->collect();

        $this->assertSame($first['fingerprint'], $second['fingerprint']);
        $this->assertSame($first['version'], 1);
        $this->assertSame($first['masked'], $second['masked']);
        $this->assertStringStartsWith('•••• ', $first['masked']);
        $this->assertNotEmpty($first['components']['machine_hash']);
        $this->assertNotEmpty($first['components']['disk_hash']);
    }

    public function test_mask_helper_formats_fingerprint_safely(): void
    {
        $masked = $this->collector->mask('9315c8bf00f861ab6447e5a59974e991568cb1a067d768a89b8e9139c3813c25');

        $this->assertSame('•••• 3C25', $masked);
        $this->assertSame('•••• 0000', $this->collector->mask(null));
    }

    public function test_exact_fingerprint_verification_passes_with_perfect_score(): void
    {
        $collected = $this->collector->collect();
        $result = $this->collector->verify($collected, $collected);

        $this->assertTrue($result['match']);
        $this->assertTrue($result['tolerance_passed']);
        $this->assertSame(1.0, $result['score']);
    }

    public function test_single_component_change_passes_tolerance(): void
    {
        $base = $this->collector->collect(overrides: [
            'machine_id' => 'host-uuid-1234',
            'disk_id' => 'disk-uuid-5678',
            'cpu_info' => 'Apple M2 Pro|cores:12|arch:arm64',
            'os_info' => 'darwin|Darwin|24.3.0',
        ]);

        // CPU upgraded or OS upgraded, machine and disk remain identical (80% similarity score)
        $modified = $this->collector->collect(overrides: [
            'machine_id' => 'host-uuid-1234',
            'disk_id' => 'disk-uuid-5678',
            'cpu_info' => 'Apple M3 Max|cores:16|arch:arm64',
            'os_info' => 'darwin|Darwin|24.4.0',
        ]);

        $result = $this->collector->verify($base, $modified);

        $this->assertFalse($result['match']);
        $this->assertTrue($result['tolerance_passed']);
        $this->assertGreaterThanOrEqual(0.70, $result['score']);
    }

    public function test_strong_machine_change_fails_tolerance_and_flags_host_swap(): void
    {
        $hostA = $this->collector->collect(overrides: [
            'machine_id' => 'host-uuid-AAAA',
            'disk_id' => 'disk-uuid-1111',
            'cpu_info' => 'Intel Core i5|cores:4|arch:x86_64',
            'os_info' => 'windows|Windows|10.0.19045',
        ]);

        // Cloned directory on completely different machine
        $hostB = $this->collector->collect(overrides: [
            'machine_id' => 'host-uuid-BBBB',
            'disk_id' => 'disk-uuid-2222',
            'cpu_info' => 'Intel Core i7|cores:8|arch:x86_64',
            'os_info' => 'windows|Windows|10.0.19045',
        ]);

        $result = $this->collector->verify($hostA, $hostB);

        $this->assertFalse($result['match']);
        $this->assertFalse($result['tolerance_passed']);
        $this->assertLessThan(0.70, $result['score']);
    }

    public function test_windows_and_linux_fixture_parsing(): void
    {
        $win = $this->collector->collect('windows', [
            'machine_id' => '23287F19-5D1C-4B9E-9F9C-5D2E984D7C31',
            'disk_id' => 'A8C2-45E1',
            'cpu_info' => 'Intel(R) Core(TM) i5-10400 CPU @ 2.90GHz|arch:x86_64',
            'os_info' => 'windows|Windows_NT|10.0.19045',
        ]);

        $this->assertSame(1, $win['version']);
        $this->assertNotEmpty($win['fingerprint']);
        $this->assertStringStartsWith('•••• ', $win['masked']);

        $linux = $this->collector->collect('linux', [
            'machine_id' => 'b7e289e6e4f3473187ca04f85e49f85c',
            'disk_id' => '65b4c102-1244-4822-9213-9f893fae1201',
            'cpu_info' => 'AMD Ryzen 5 5600G with Radeon Graphics|arch:x86_64',
            'os_info' => 'linux|Linux|6.8.0',
        ]);

        $this->assertSame(1, $linux['version']);
        $this->assertNotEmpty($linux['fingerprint']);
        $this->assertStringStartsWith('•••• ', $linux['masked']);
    }
}
