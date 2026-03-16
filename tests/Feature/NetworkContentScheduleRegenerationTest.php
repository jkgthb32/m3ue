<?php

use App\Models\Channel;
use App\Models\Episode;
use App\Models\Network;
use App\Models\NetworkContent;
use App\Models\NetworkProgramme;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('regenerates schedule when content is added to sequential network', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'sequential',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $channel1 = Channel::factory()->create();
    $channel2 = Channel::factory()->create();

    // Add first content item
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel1->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    // Verify schedule was generated
    $programmeCount = $network->programmes()->count();
    expect($programmeCount)->toBeGreaterThan(0);

    // Act: Add second content item
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel2->id,
        'sort_order' => 2,
        'weight' => 1,
    ]);

    // Assert: Schedule should be regenerated with both items
    $newProgrammeCount = $network->fresh()->programmes()->count();
    expect($newProgrammeCount)->toBeGreaterThan(0);
    expect($network->fresh()->schedule_generated_at)->not->toBeNull();
});

it('regenerates schedule when content is added to shuffle network', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'shuffle',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $channel = Channel::factory()->create();

    // Act: Add content item
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    // Assert: Schedule should be generated
    expect($network->fresh()->programmes()->count())->toBeGreaterThan(0);
    expect($network->fresh()->schedule_generated_at)->not->toBeNull();
});

it('does not regenerate schedule for manual schedule networks', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'manual',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $channel = Channel::factory()->create();

    // Manually create a programme
    NetworkProgramme::create([
        'network_id' => $network->id,
        'title' => 'Manual Programme',
        'start_time' => Carbon::now(),
        'end_time' => Carbon::now()->addHour(),
        'duration_seconds' => 3600,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel->id,
    ]);

    $initialProgrammeCount = $network->programmes()->count();
    expect($initialProgrammeCount)->toBe(1);

    // Act: Add content item
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    // Assert: Schedule should NOT be auto-regenerated (manual schedules are managed via UI)
    $finalProgrammeCount = $network->fresh()->programmes()->count();
    expect($finalProgrammeCount)->toBe($initialProgrammeCount);
});

it('does not regenerate schedule when auto_regenerate_schedule is disabled', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'sequential',
        'loop_content' => true,
        'auto_regenerate_schedule' => false, // Disabled
    ]);

    $channel = Channel::factory()->create();

    // Act: Add content item
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    // Assert: Schedule should NOT be generated
    expect($network->fresh()->programmes()->count())->toBe(0);
    expect($network->fresh()->schedule_generated_at)->toBeNull();
});

it('regenerates schedule when multiple items are added sequentially', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'sequential',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $channels = Channel::factory()->count(3)->create();

    // Act: Add multiple content items sequentially
    foreach ($channels as $index => $channel) {
        NetworkContent::create([
            'network_id' => $network->id,
            'contentable_type' => Channel::class,
            'contentable_id' => $channel->id,
            'sort_order' => $index + 1,
            'weight' => 1,
        ]);
    }

    // Assert: Schedule should be generated with all items
    $network->refresh();
    expect($network->programmes()->count())->toBeGreaterThan(0);
    expect($network->schedule_generated_at)->not->toBeNull();

    // Verify the schedule includes programmes
    $programmeContentIds = $network->programmes()
        ->pluck('contentable_id')
        ->unique()
        ->sort()
        ->values()
        ->toArray();

    expect(count($programmeContentIds))->toBeGreaterThan(0);
});

it('regenerates schedule correctly when adding episodes', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'sequential',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $episode = Episode::factory()->create();

    // Act: Add episode
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Episode::class,
        'contentable_id' => $episode->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    // Assert: Schedule should be generated
    expect($network->fresh()->programmes()->count())->toBeGreaterThan(0);
    expect($network->fresh()->schedule_generated_at)->not->toBeNull();
});

it('handles mixed content types when regenerating schedule', function () {
    // Arrange
    Carbon::setTestNow('2026-03-15 10:00:00');

    $network = Network::factory()->create([
        'schedule_type' => 'sequential',
        'loop_content' => true,
        'auto_regenerate_schedule' => true,
    ]);

    $channel = Channel::factory()->create();
    $episode = Episode::factory()->create();

    // Act: Add mixed content types
    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Channel::class,
        'contentable_id' => $channel->id,
        'sort_order' => 1,
        'weight' => 1,
    ]);

    NetworkContent::create([
        'network_id' => $network->id,
        'contentable_type' => Episode::class,
        'contentable_id' => $episode->id,
        'sort_order' => 2,
        'weight' => 1,
    ]);

    // Assert: Schedule should be generated with both types
    $network->refresh();
    expect($network->programmes()->count())->toBeGreaterThan(0);
    expect($network->schedule_generated_at)->not->toBeNull();

    // Verify both content types appear in schedule
    $contentTypes = $network->programmes()
        ->pluck('contentable_type')
        ->unique()
        ->toArray();

    expect($contentTypes)->toContain(Channel::class);
    expect($contentTypes)->toContain(Episode::class);
});
