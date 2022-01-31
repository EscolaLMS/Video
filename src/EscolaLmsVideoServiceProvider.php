<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Repositories\Contracts\TopicRepositoryContract;
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
        app(TopicRepositoryContract::class)->registerContentClass(Video::class);
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
                $topic->save();
                ProcessVideo::dispatch($video);
            }
        }));

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        \EscolaLms\TopicTypes\Http\Resources\TopicType\Client\VideoResource::extend(fn($thisObj) => [
            'value' => $thisObj->hls,
            'url' => $thisObj->hls ? Storage::disk('local')->url($thisObj->hls) : null,
        ]);

        \EscolaLms\TopicTypes\Http\Resources\TopicType\Admin\VideoResource::extend(fn($thisObj) => [
            'hls' => $thisObj->hls,
            'hls_url' => $thisObj->hls ? Storage::disk('local')->url($thisObj->hls) : null,
        ]);

    }

    public function bootForConsole()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
