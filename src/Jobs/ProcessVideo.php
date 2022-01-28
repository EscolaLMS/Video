<?php

namespace EscolaLms\Video\Jobs;

use EscolaLms\Courses\Models\Topic;
use EscolaLms\Video\Models\Video;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
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

    public function handle(): bool
    {
        $video = $this->video;
        $topic = $this->topic;
        $input = $video->value;
        $dir = dirname($input);
        $hlsPath = $dir . '/hls.m3u8';

        $this->clearDirectory($dir, $video->value);

        try {
            $this->process($video, $input, $hlsPath);

            $this->updateVideoState(['ffmpeg' => [
                'state' => 'starting'
            ]]);
            $topic->save();
        } catch (RuntimeException $exception) {
            $this->updateVideoState(['ffmpeg' => [
                'state' => 'error',
                'message' => $exception->getMessage()
            ]]);

            return false;
        }

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'finished',
            'path' => $hlsPath,
        ]]);

        return true;
    }

    private function process(Video $video, string $input, string $hlsPath): void {
        $lowBitrate = (new X264)->setKiloBitrate(250);
        $midBitrate = (new X264)->setKiloBitrate(500);
        $highBitrate = (new X264)->setKiloBitrate(1000);
        $superBitrate = (new X264)->setKiloBitrate(1500);

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
            ->save($hlsPath);
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

    private function clearDirectory(string $dir, string $video): bool
    {
        $storage = Storage::disk($this->disk);

        if ($storage->exists($dir)) {
            $files = $storage->allFiles($dir);

            foreach ($files as $file) {
                if ($file === $video) {
                    continue;
                }

                $storage->delete($file);
            }
        }

        return true;
    }
}
