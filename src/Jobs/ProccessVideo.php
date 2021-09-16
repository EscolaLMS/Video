<?php

namespace EscolaLms\Video\Jobs;

use EscolaLms\Courses\Models\TopicContent\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;


class ProccessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;


    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    private function updateVideoState($state)
    {
        $video = $this->video;
        $arr = is_array($video->topic->json) ? $video->topic->json : [];
        $video->topic->json = array_merge($arr, $state);
        $video->topic->save();
    }

    public function handle()
    {

        $video = $this->video;
        $input = $video->value;
        $dir = dirname($input);

        $lowBitrate = (new X264)->setKiloBitrate(250);
        $midBitrate = (new X264)->setKiloBitrate(500);
        $highBitrate = (new X264)->setKiloBitrate(1000);
        $superBitrate = (new X264)->setKiloBitrate(1500);

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'starting'
        ]]);

        $video->topic->save();

        FFMpeg::fromDisk('local')
            ->open($input)
            ->exportForHLS()
            /*
            ->addFormat($lowBitrate, function ($media) {
                $media->addFilter('scale=640:480');
            })
            */
            ->addFormat($midBitrate, function ($media) {
                $media->scale(640, 480);
            })
            ->addFormat($highBitrate, function ($media) {
                $media->scale(1280, 720);
            })
            /*
            ->addFormat($superBitrate, function ($media) {
                $media->scale(1280, 720);
            })
            */
            ->onProgress(function ($percentage) use ($video) {
                $this->updateVideoState(['ffmpeg' => [
                    'state' => 'coding',
                    'percentage' => $percentage
                ]]);
            })
            ->save($dir . '/hls.m3u8');

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'finished',
            'path' => $dir . '/hls.m3u8'
        ]]);


        return true;


        /*
        FFMpeg::fromDisk('local')
            ->open($this->video->value)
            ->export()
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save('small_steve.mkv');
        file_put_contents(__DIR__ . '/success', $this->video->value);
        */
        // Process uploaded podcast...
    }
}
