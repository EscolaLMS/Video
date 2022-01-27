<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Repositories\Contracts\TopicRepositoryContract;
use EscolaLms\TopicTypes\Events\VideoUpdated;
use EscolaLms\Video\Jobs\ProcessVideo;
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
        app(TopicRepositoryContract::class)->registerContentClass(Video::class);
    }

    public function boot()
    {
        Event::listen(queueable(function (VideoUpdated $event) {
            $video = Video::find($event->getVideo()->getKey());
            $topic = $video->topic;

            if (isset($topic)) {
                $arr = is_array($topic->json) ? $topic->json : [];
                $topic->json = array_merge($arr, ['ffmpeg' => [
                    'state' => 'queue'
                ]]);
                $topic->save();
                ProcessVideo::dispatch($video);
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
