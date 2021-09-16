<?php

namespace EscolaLms\Video;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use EscolaLms\Courses\Events\VideoUpdated;
use function Illuminate\Events\queueable;
use EscolaLms\Video\Jobs\ProccessVideo;
use EscolaLms\Courses\Models\TopicContent\Video;

use Throwable;

/**
 * SWAGGER_VERSION
 */

class EscolaLmsVideoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(queueable(function (VideoUpdated $event) {
            $video = $event->getVideo();
            $arr = is_array($video->topic->json) ? $video->topic->json : [];
            $video->topic->json = array_merge($arr, ['ffmpeg' => [
                'state' => 'queue'
            ]]);
            $video->topic->save();
            ProccessVideo::dispatch($video);
        }));
    }
}
