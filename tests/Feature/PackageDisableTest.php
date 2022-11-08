<?php

namespace EscolaLms\Video\Tests\Feature;

use EscolaLms\Core\Tests\CreatesUsers;
use EscolaLms\Courses\Database\Seeders\CoursesPermissionSeeder;
use EscolaLms\TopicTypes\Events\TopicTypeChanged;
use EscolaLms\Video\Database\Seeders\VideoPermissionSeeder;
use EscolaLms\Video\Tests\TestCase;
use EscolaLms\Video\Tests\VideoTesting;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;

class PackageDisableTest extends TestCase
{
    use DatabaseTransactions, CreatesUsers, VideoTesting;

    protected function setUp(): void
    {
        putenv("VIDEO_PROCESSING_ENABLE=false");
        parent::setUp();

        $this->seed(VideoPermissionSeeder::class);
        $this->seed(CoursesPermissionSeeder::class);
    }

    public function testVideoPackageDisable(): void
    {
        Queue::fake();

        TopicTypeChanged::dispatch($this->makeAdmin(), $this->createVideo());

        Queue::assertNotPushed(CallQueuedListener::class);
    }

    public function testVideoPackageDisableExtendableResource(): void
    {
        $course = $this->createCourse();
        $this->actingAs($this->makeAdmin())
            ->getJson('/api/courses/' . $course->getKey() . '/program')
            ->assertOk()
            ->assertJsonMissing(['data' => [
                'lessons' => [[
                    'topics' =>[[
                        'topicable' => [
                            'hls',
                            'hls_url',
                        ]
                    ]]
                ]]
            ]]);
    }
}
