<?php

namespace EscolaLms\Video\Tests\Feature;

use EscolaLms\Courses\Models\Course;
use EscolaLms\Courses\Models\Lesson;
use EscolaLms\Courses\Models\Topic;
use EscolaLms\Courses\Models\TopicContent\Video;
use EscolaLms\Video\Jobs\ProccessVideo;
use EscolaLms\Video\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class VideoTest extends TestCase
{
    /**
     * @group expensive
     */
    public function testLocal()
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->getKey()]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->getKey()]);
        $video = new Video();

        $path = __DIR__ . '/../samples/sample.mp4';

        $this->assertFileExists($path);

        $file = fopen($path, 'r');

        $this->assertIsResource($file);

        $targetPath = 'video_test/' . Carbon::now()->format('Y_m_d_His') . '/sample.mp4';

        if (!Storage::disk('local')->exists($targetPath)) {
            $success = Storage::disk('local')->put($targetPath, $file);
            $this->assertTrue($success);
        }

        $fullTargetPath = Storage::disk('local')->path($targetPath);

        $this->assertFileExists($fullTargetPath);

        $video->value = $targetPath;
        $video->save();
        $video->topic()->save($topic);

        $job = new ProccessVideo($video);
        $job->handle();

        $video->refresh();
        $json = $video->topic->json;

        Storage::disk('local')->assertExists($json['ffmpeg']['path']);

        $fullPlaylistPath = Storage::disk('local')->path($json['ffmpeg']['path']);
        $this->assertFileExists($fullPlaylistPath);
    }

    /**
     * @group expensive
     */
    public function testS3()
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->getKey()]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->getKey()]);
        $video = new Video();

        $path = __DIR__ . '/../samples/sample.mp4';

        $this->assertFileExists($path);

        $file = fopen($path, 'r');

        $this->assertIsResource($file);

        $targetPath = 'video_test/' . Carbon::now()->format('Y_m_d_His') . '/sample.mp4';

        if (!Storage::disk('s3')->exists($targetPath)) {
            $success = Storage::disk('s3')->put($targetPath, $file);
            $this->assertTrue($success);
        }

        $video->value = $targetPath;
        $video->save();
        $video->topic()->save($topic);

        $job = new ProccessVideo($video, 's3');
        $job->handle();

        $video->refresh();
        $json = $video->topic->json;
        Storage::disk('s3')->assertExists($json['ffmpeg']['path']);
    }
}
