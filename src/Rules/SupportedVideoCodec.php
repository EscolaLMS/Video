<?php

namespace EscolaLms\Video\Rules;

use Exception;
use FFMpeg\FFProbe;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SupportedVideoCodec implements Rule
{
    private string $message = '';

    public function passes($attribute, $value): bool
    {
        // Only a freshly uploaded file can be probed; an existing stored path is left untouched.
        if (!$value instanceof UploadedFile) {
            return true;
        }

        $allowedVideo = $this->parseCodecList(config('escolalms_video.allowed_video_codecs'));
        $allowedAudio = $this->parseCodecList(config('escolalms_video.allowed_audio_codecs'));

        // Opt-in feature: with no allow-list configured there is no restriction.
        if (empty($allowedVideo) && empty($allowedAudio)) {
            return true;
        }

        try {
            $streams = FFProbe::create()->streams($value->getPathname());
            $videoCodec = optional($streams->videos()->first())->get('codec_name');
            $audioCodec = optional($streams->audios()->first())->get('codec_name');
        } catch (Exception $exception) {
            Log::error('Video codec validation could not probe the file: ' . $exception->getMessage());
            $this->message = __('video::validation.codec_unreadable');
            return false;
        }

        if (!empty($allowedVideo) && !in_array($videoCodec, $allowedVideo, true)) {
            $this->message = __('video::validation.unsupported_video_codec', [
                'codec' => $videoCodec ?: '—',
                'allowed' => implode(', ', $allowedVideo),
            ]);
            return false;
        }

        // A silent video (no audio stream) is allowed; only a present, disallowed audio codec fails.
        if (!empty($allowedAudio) && $audioCodec !== null && !in_array($audioCodec, $allowedAudio, true)) {
            $this->message = __('video::validation.unsupported_audio_codec', [
                'codec' => $audioCodec,
                'allowed' => implode(', ', $allowedAudio),
            ]);
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * Accepts a comma-separated string (e.g. "h264,hevc") as configured from the admin panel;
     * an array is also tolerated in case the value was set that way.
     */
    private function parseCodecList($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $items = explode(',', $value);
        } else {
            return [];
        }

        return array_values(array_filter(array_map('trim', $items), fn ($codec) => $codec !== ''));
    }
}
