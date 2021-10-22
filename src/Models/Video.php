<?php

namespace EscolaLms\Video\Models;

use EscolaLms\Courses\Models\TopicContent\Video as TopicContentVideo;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *      schema="TopicVideo",
 *      required={"value"},
 *      @OA\Property(
 *          property="id",
 *          description="id",
 *          @OA\Schema(
 *             type="integer",
 *         )
 *      ),
 *      @OA\Property(
 *          property="value",
 *          description="value",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="poster",
 *          description="poster",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="hls",
 *          description="hls",
 *          type="string"
 *      )
 * )
 */
class Video extends TopicContentVideo
{

    public $fillable = [
        'value',
        'poster',
        'width',
        'height',
        'hls',
    ];

    protected $casts = [
        'id' => 'integer',
        'value' => 'string',
        'poster' => 'string',
        'width' => 'integer',
        'height' => 'integer',
        'hls' => 'string'
    ];

    protected $appends = ['url', 'poster_url', 'hls_url'];

    public function getHlsUrlAttribute(): ?string
    {
        if (isset($this->hls)) {
            return url(Storage::url($this->hls));
        }
        return null;
    }
}
