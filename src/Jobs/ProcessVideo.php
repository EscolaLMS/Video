<?php

namespace EscolaLms\Video\Jobs;

use EscolaLms\Courses\Models\Topic;
use EscolaLms\Video\Events\ProcessVideoFailed;
use EscolaLms\Video\Events\ProcessVideoStarted;
use EscolaLms\Video\Models\Video;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 18000;


    protected Video $video;
    protected Topic $topic;
    protected Authenticatable $user;
    protected string $disk;

    public function __construct(Video $video, Authenticatable $user, string $disk = 'local')
    {
        $this->video = $video;
        $this->topic = $video->topic;
        $this->user = $user;
        $this->disk = $disk;
    }

    public function handle(): bool
    {
        $video = $this->video;
        $topic = $this->topic;
        $input = $video->value;
        $dir = dirname($input);
        $hlsPath = $dir . '/hls.m3u8';

        ProcessVideoStarted::dispatch($this->user, $topic);

        $this->clearDirectory($dir, $video->value);

        $this->process($video, $input, $hlsPath);

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'starting'
        ]]);
        $topic->save();

        $this->updateVideoState(['ffmpeg' => [
            'state' => 'finished',
            'path' => $hlsPath,
        ]]);

        return true;
    }

    public function failed(\Exception $exception)
    {
        $this->updateVideoState(['ffmpeg' => [
            'state' => 'error',
            'message' => $exception->getMessage()
        ]]);

        ProcessVideoFailed::dispatch($this->user, $this->topic);
    }

    private function getFFMpeg(string $input): HLSExporter
    {
        $bitRates = config('escolalms_video.bitrates');

        $ffmpeg = FFMpeg::fromDisk($this->disk)
            ->open($input)
            ->exportForHLS();

        foreach ($bitRates as $bitRate) {
            $value = (new X264)->setKiloBitrate($bitRate['kiloBitrate']);
            $ffmpeg->addFormat($value, function ($media) use ($bitRate) {
                $scale = 'scale=' . $bitRate['scale'];
                $media->addFilter($scale);
            });
        }

        return $ffmpeg;
    }

    private function process(Video $video, string $input, string $hlsPath): void {
        $this
            ->getFFMpeg($input)
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
        $topic = $this->topic;

        $arr = is_array($topic->json) ? $topic->json : [];
        $topic->json = array_merge($arr, $state);
        $topic->save();

        if ($state['ffmpeg']['state'] === 'finished') {
            $topic->active = true;
            $topic->save();
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
