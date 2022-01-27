<?php

namespace EscolaLms\Video\Jobs;

use EscolaLms\Courses\Models\Topic;
use EscolaLms\Video\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Video $video;
    protected Topic $topic;
    protected string $disk;

    public function __construct(Video $video, string $disk = 'local')
    {
        $this->video = $video;
        $this->topic = $video->topic;
        $this->disk = $disk;
    }

    private function updateVideoState($state): void
    {
        $video = $this->video;
        $topic = $this->topic;

        $arr = is_array($topic->json) ? $topic->json : [];
        $topic->json = array_merge($arr, $state);
        $topic->save();

        if ($state['ffmpeg']['state'] === 'finished') {
            $video->hls = $state['ffmpeg']['path'];
            $video->save();
        }
    }

    public function handle(): bool
    {
        $video = $this->video;
        $topic = $this->topic;
        $input = $video->value;
        $dir = dirname($input);
        $hlsFilePath = $dir . '/hls.m3u8';

        $lowBitrate = (new X264)->setKiloBitrate(250);
        $midBitrate = (new X264)->setKiloBitrate(500);
        $highBitrate = (new X264)->setKiloBitrate(1000);
        $superBitrate = (new X264)->setKiloBitrate(1500);

        // TODO here check status of current job
        // if it is processing then return
        // check hash (eg md5) of current video file to prevent converting again same video

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'starting'
        ]]);

        $topic->save();

        FFMpeg::fromDisk($this->disk)
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
            ->save($hlsFilePath);

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'finished',
            'path' => $hlsFilePath,
            // 'md5' => md5($video->value)
        ]]);

        return true;
    }
}
