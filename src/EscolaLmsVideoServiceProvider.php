<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Facades\Topic;
use EscolaLms\TopicTypes\Events\TopicTypeChanged;
use EscolaLms\TopicTypes\Http\Resources\TopicType\Admin\VideoResource as VideoAdminResource;
use EscolaLms\TopicTypes\Http\Resources\TopicType\Client\VideoResource as VideoClientResource;
use EscolaLms\Video\Jobs\ProcessVideo;
use EscolaLms\Video\Models\Video;
use EscolaLms\Video\Providers\SettingsServiceProvider;
use EscolaLms\Video\Repositories\Contracts\VideoRepositoryContract;
use EscolaLms\Video\Repositories\VideoRepository;
use Illuminate\Support\Facades\Storage;
use function Illuminate\Events\queueable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EscolaLmsVideoServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'escolalms_video';

    public $singletons = [
        VideoRepositoryContract::class => VideoRepository::class,
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', self::CONFIG_KEY);

        $this->app->register(AuthServiceProvider::class);
        $this->app->register(SettingsServiceProvider::class);

        Topic::registerContentClass(Video::class);
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (config('escolalms_video.enable')) {
            Event::listen(queueable(function (TopicTypeChanged $event) {
                if (($event->getTopicContent() instanceof \EscolaLms\TopicTypes\Models\TopicContent\Video)) {
                    $video = Video::findOrFail($event->getTopicContent()->getKey());
                    $topic = $video->topic;

                    if (isset($topic)) {
                        $arr = is_array($topic->json) ? $topic->json : [];
                        $topic->json = array_merge($arr, ['ffmpeg' => [
                            'state' => 'queue'
                        ]]);
                        $topic->active = false;
                        $topic->save();
                        ProcessVideo::dispatch($video, $event->getUser());
                    }
                }
            }));

            $this->extendResources();
        }
    }

    public function bootForConsole()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/config.php' => config_path(self::CONFIG_KEY . '.php'),
        ], self::CONFIG_KEY . '.config');
    }

    private function extendResources(): void
    {
        VideoClientResource::extend(function($thisObj) {
            $json = $thisObj->topic->json;
            $json = is_array($json) ? $json : json_decode($json, true);

            return [
                'value' => $json['ffmpeg']['path'] ?? null,
                'url' => isset($json['ffmpeg']['path']) ? Storage::url($json['ffmpeg']['path']) : null,
            ];
        });

        VideoAdminResource::extend(function($thisObj) {
            $json = $thisObj->topic->json;
            $json = is_array($json) ? $json : json_decode($json, true);

            return [
                'hls' => $json['ffmpeg']['path'] ?? null,
                'hls_url' => isset($json['ffmpeg']['path']) ? Storage::url($json['ffmpeg']['path']) : null,
            ];
        });
    }
}
