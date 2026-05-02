<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Models;

use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Orchestra\Testbench\TestCase;

class WeddingOrganizerTest extends TestCase
{
    // ── getCityAttribute() ────────────────────────────────────────────────

    public function test_returns_unknown_when_address_is_null(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = null;
        $this->assertSame('Unknown', $wo->city);
    }

    public function test_returns_full_address_when_one_part(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = 'Jakarta';
        $this->assertSame('Jakarta', $wo->city);
    }

    public function test_returns_full_address_when_two_parts(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = 'Bandung, Jawa Barat';
        $this->assertSame('Bandung, Jawa Barat', $wo->city);
    }

    public function test_returns_last_two_parts_for_long_address(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = 'Jl. Sudirman No. 1, Menteng, Jakarta Pusat, DKI Jakarta, Indonesia';
        $this->assertSame('Jakarta Pusat, DKI Jakarta', $wo->city);
    }

    public function test_strips_indonesia_suffix(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = 'Kota Surabaya, Jawa Timur, Indonesia';
        $this->assertSame('Kota Surabaya, Jawa Timur', $wo->city);
    }

    public function test_returns_unknown_for_empty_address(): void
    {
        $wo = new WeddingOrganizer();
        $wo->address = '';
        $this->assertSame('Unknown', $wo->city);
    }

    // ── getLocationAttribute() ────────────────────────────────────────────

    public function test_returns_location_as_lat_lng_array(): void
    {
        $wo = new WeddingOrganizer();
        $wo->latitude = '-6.2088';
        $wo->longitude = '106.8456';

        $location = $wo->location;
        $this->assertSame(-6.2088, $location['lat']);
        $this->assertSame(106.8456, $location['lng']);
    }

    public function test_set_location_sets_lat_and_lng(): void
    {
        $wo = new WeddingOrganizer();
        $wo->location = ['lat' => -7.2575, 'lng' => 112.7521];

        $this->assertSame(-7.2575, $wo->latitude);
        $this->assertSame(112.7521, $wo->longitude);
    }

    public function test_set_location_does_nothing_when_null(): void
    {
        $wo = new WeddingOrganizer();
        $wo->latitude = '-6.0';
        $wo->longitude = '106.0';
        $wo->location = null;

        $this->assertSame('-6.0', $wo->latitude);
        $this->assertSame('106.0', $wo->longitude);
    }

    // ── getOperationalHoursAttribute() ───────────────────────────────────

    public function test_returns_default_operational_hours(): void
    {
        $wo = new WeddingOrganizer();
        $this->assertSame('Senin - Minggu: 09:00 - 18:00', $wo->operational_hours);
    }

    public function test_returns_custom_operational_hours(): void
    {
        $wo = new WeddingOrganizer();
        $wo->setRawAttributes(['operational_hours' => 'Senin - Jumat: 08:00 - 17:00']);
        $this->assertSame('Senin - Jumat: 08:00 - 17:00', $wo->operational_hours);
    }
}
