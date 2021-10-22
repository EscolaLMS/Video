<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Events\VideoUpdated;
use EscolaLms\Courses\Models\TopicContent\Video as TopicContentVideo;
use EscolaLms\Courses\Repositories\TopicRepository;
use EscolaLms\Video\Jobs\ProccessVideo;
use EscolaLms\Video\Models\Video;
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
        TopicRepository::unregisterContentClass(TopicContentVideo::class);
        TopicRepository::registerContentClass(Video::class);
    }

    public function boot()
    {
        Event::listen(queueable(function (VideoUpdated $event) {
            $video = Video::find($event->getVideo()->getKey());
            if (isset($video->topic)) {
                $video->topic->topicable_type = Video::class;
                $arr = is_array($video->topic->json) ? $video->topic->json : [];
                $video->topic->json = array_merge($arr, ['ffmpeg' => [
                    'state' => 'queue'
                ]]);
                $video->topic->save();
                ProccessVideo::dispatch($video);
            }
        }));

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    public function bootForConsole()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
