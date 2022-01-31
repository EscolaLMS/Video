<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Facades\Topic;
use EscolaLms\TopicTypes\Events\TopicTypeChanged;
use EscolaLms\Video\Jobs\ProcessVideo;
use EscolaLms\Video\Models\Video;
use Illuminate\Support\Facades\Storage;
use function Illuminate\Events\queueable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * SWAGGER_VERSION
 */
class EscolaLmsVideoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'escolalms_video');

        Topic::registerContentClass(Video::class);
    }

    public function boot()
    {
        Event::listen(queueable(function (TopicTypeChanged $event) {
            if (!($event->getTopicContent() instanceof \EscolaLms\TopicTypes\Models\TopicContent\Video)) {
                return;
            }

            $video = Video::findOrFail($event->getTopicContent()->getKey());
            $topic = $video->topic;

            if (isset($topic)) {
                $arr = is_array($topic->json) ? $topic->json : [];
                $topic->json = array_merge($arr, ['ffmpeg' => [
                    'state' => 'queue'
                ]]);
                $topic->active = false;
                $topic->save();
                ProcessVideo::dispatch($video);
            }
        }));

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        \EscolaLms\TopicTypes\Http\Resources\TopicType\Client\VideoResource::extend(function($thisObj) {
            $json = $thisObj->topic->json;

            return [
                'value' => $json['ffmpeg']['path'] ?? null,
                'url' => isset($json['ffmpeg']['path']) ? Storage::disk('local')->url($json['ffmpeg']['path']) : null,
            ];
        });

        \EscolaLms\TopicTypes\Http\Resources\TopicType\Admin\VideoResource::extend(function($thisObj) {
            $json = $thisObj->topic->json;

            return [
                'hls' => $json['ffmpeg']['path'] ?? null,
                'hls_url' => isset($json['ffmpeg']['path']) ? Storage::disk('local')->url($json['ffmpeg']['path']) : null,
            ];
        });

    }

    public function bootForConsole()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/config.php' => config_path('escolalms_video.php'),
        ], 'escolalms_video.config');
    }
}
