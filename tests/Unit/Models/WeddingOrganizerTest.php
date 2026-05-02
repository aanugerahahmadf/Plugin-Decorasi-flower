<?php

use Aanugerah\WeddingPro\Models\WeddingOrganizer;

// ── getCityAttribute() ────────────────────────────────────────────────────

it('returns Unknown when address is null', function () {
    $wo = new WeddingOrganizer();
    $wo->address = null;

    expect($wo->city)->toBe('Unknown');
});

it('returns full address when only 1 part', function () {
    $wo = new WeddingOrganizer();
    $wo->address = 'Jakarta';

    expect($wo->city)->toBe('Jakarta');
});

it('returns full address when only 2 parts', function () {
    $wo = new WeddingOrganizer();
    $wo->address = 'Bandung, Jawa Barat';

    expect($wo->city)->toBe('Bandung, Jawa Barat');
});

it('returns last 2 parts for long address', function () {
    $wo = new WeddingOrganizer();
    $wo->address = 'Jl. Sudirman No. 1, Menteng, Jakarta Pusat, DKI Jakarta, Indonesia';

    expect($wo->city)->toBe('DKI Jakarta');
});

it('strips Indonesia suffix from long address', function () {
    $wo = new WeddingOrganizer();
    $wo->address = 'Kota Surabaya, Jawa Timur, Indonesia';

    expect($wo->city)->toBe('Kota Surabaya, Jawa Timur');
});

it('returns Unknown for empty string address', function () {
    $wo = new WeddingOrganizer();
    $wo->address = '';

    expect($wo->city)->toBe('Unknown');
});

// ── getLocationAttribute() ────────────────────────────────────────────────

it('returns location as lat/lng array', function () {
    $wo = new WeddingOrganizer();
    $wo->latitude = '-6.2088';
    $wo->longitude = '106.8456';

    expect($wo->location)->toMatchArray([
        'lat' => -6.2088,
        'lng' => 106.8456,
    ]);
});

it('setLocationAttribute sets latitude and longitude', function () {
    $wo = new WeddingOrganizer();
    $wo->location = ['lat' => -7.2575, 'lng' => 112.7521];

    expect($wo->latitude)->toBe(-7.2575);
    expect($wo->longitude)->toBe(112.7521);
});

it('setLocationAttribute does nothing when null', function () {
    $wo = new WeddingOrganizer();
    $wo->latitude = '-6.0';
    $wo->longitude = '106.0';
    $wo->location = null;

    // Tidak berubah
    expect($wo->latitude)->toBe('-6.0');
    expect($wo->longitude)->toBe('106.0');
});

// ── getOperationalHoursAttribute() ───────────────────────────────────────

it('returns default operational hours when not set', function () {
    $wo = new WeddingOrganizer();

    expect($wo->operational_hours)->toBe('Senin - Minggu: 09:00 - 18:00');
});

it('returns custom operational hours when set', function () {
    $wo = new WeddingOrganizer();
    $wo->setRawAttributes(['operational_hours' => 'Senin - Jumat: 08:00 - 17:00']);

    expect($wo->operational_hours)->toBe('Senin - Jumat: 08:00 - 17:00');
});
