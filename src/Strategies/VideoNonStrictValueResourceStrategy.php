<?php

namespace EscolaLms\Video\Strategies;

use Illuminate\Support\Facades\Storage;

class VideoNonStrictValueResourceStrategy extends VideoResourceStrategy
{
    function clientResource($object): array
    {
        $json = json_decode($object->topic->json, true);
        $topicable = $object->topic->topicable;
        $value = $json['ffmpeg']['path'] ?? $topicable->value;

        return [
            'value' => $value,
            'url' => Storage::url($value),
        ];
    }

    function adminResource($object): array
    {
        $json = json_decode($object->topic->json, true);

        return [
            'hls' => $json['ffmpeg']['path'] ?? null,
            'hls_url' => isset($json['ffmpeg']['path']) ? Storage::url($json['ffmpeg']['path']) : null,
        ];
    }
}
