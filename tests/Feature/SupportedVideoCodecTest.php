<?php

namespace EscolaLms\Video\Tests\Feature;

use EscolaLms\Video\Rules\SupportedVideoCodec;
use EscolaLms\Video\Tests\TestCase;
use FFMpeg\FFProbe;
use Illuminate\Http\UploadedFile;

class SupportedVideoCodecTest extends TestCase
{
    private function sampleFile(): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/../samples/sample.mp4',
            'sample.mp4',
            'video/mp4',
            null,
            true
        );
    }

    public function testPassesWhenNoCodecsConfigured(): void
    {
        config([
            'escolalms_video.allowed_video_codecs' => '',
            'escolalms_video.allowed_audio_codecs' => '',
        ]);

        // Opt-in feature: with no allow-list the file is accepted without probing.
        $this->assertTrue((new SupportedVideoCodec())->passes('value', $this->sampleFile()));
    }

    public function testPassesForNonUploadedFile(): void
    {
        config(['escolalms_video.allowed_video_codecs' => 'h264']);

        // An already-stored path (string) is left untouched, not re-probed.
        $this->assertTrue((new SupportedVideoCodec())->passes('value', 'video/existing.mp4'));
    }

    /**
     * @group expensive
     */
    public function testRejectsWhenVideoCodecNotAllowed(): void
    {
        config(['escolalms_video.allowed_video_codecs' => 'definitely-not-a-real-codec']);

        $rule = new SupportedVideoCodec();

        $this->assertFalse($rule->passes('value', $this->sampleFile()));
        $this->assertNotEmpty($rule->message());
    }

    /**
     * @group expensive
     */
    public function testPassesWhenVideoCodecAllowed(): void
    {
        $codec = FFProbe::create()
            ->streams(__DIR__ . '/../samples/sample.mp4')
            ->videos()
            ->first()
            ->get('codec_name');

        config([
            'escolalms_video.allowed_video_codecs' => $codec,
            'escolalms_video.allowed_audio_codecs' => '',
        ]);

        $this->assertTrue((new SupportedVideoCodec())->passes('value', $this->sampleFile()));
    }
}
