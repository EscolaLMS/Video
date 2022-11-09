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
use Illuminate\Testing\Fluent\AssertableJson;

class PackageEnableTest extends TestCase
{
    use DatabaseTransactions, CreatesUsers, VideoTesting;

    protected function setUp(): void
    {
        putenv("VIDEO_PROCESSING_ENABLE=true");
        parent::setUp();

        $this->seed(VideoPermissionSeeder::class);
        $this->seed(CoursesPermissionSeeder::class);
    }

    public function testVideoPackageEnable(): void
    {
        Queue::fake();

        TopicTypeChanged::dispatch($this->makeAdmin(), $this->createVideo());

        Queue::assertPushed(CallQueuedListener::class);
    }

    public function testVideoPackageEnableExtendableResource(): void
    {
        $course = $this->createCourse();
        $this->actingAs($this->makeAdmin())
            ->getJson('/api/courses/' . $course->getKey() . '/program')
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->has('data', fn($json) =>
                $json->has('lessons', fn($json) =>
                    $json->each(fn($json) =>
                        $json->has('topics', fn($json) =>
                            $json->each(fn($json) =>
                                $json->has('topicable', fn($json) => $json
                                    ->has('url')
                                    ->has('value')
                                    ->has('hls')
                                    ->has('hls_url')
                                    ->etc()
                                )->etc()
                            )
                        )->etc()
                    )
                )->etc()
            )->etc());
    }
}
