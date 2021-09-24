<?php

namespace EscolaLms\Video;

use EscolaLms\Courses\Events\VideoUpdated;
use EscolaLms\Video\Jobs\ProccessVideo;
use function Illuminate\Events\queueable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * SWAGGER_VERSION
 */

class EscolaLmsVideoServiceProvider extends ServiceProvider
{
    public function boot()
    {

        Event::listen(queueable(function (VideoUpdated $event) {
            $video = $event->getVideo();
            if (isset($video->topic)) {
                $arr = is_array($video->topic->json) ? $video->topic->json : [];
                $video->topic->json = array_merge($arr, ['ffmpeg' => [
                    'state' => 'queue'
                ]]);
                $video->topic->save();
                ProccessVideo::dispatch($video);
            }
        }));
    }

    public function register()
    {
    }
}
