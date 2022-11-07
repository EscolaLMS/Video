<?php

namespace EscolaLms\Video\Repositories;

use EscolaLms\Core\Repositories\BaseRepository;
use EscolaLms\Video\Models\Video;
use EscolaLms\Video\Repositories\Contracts\VideoRepositoryContract;

class VideoRepository extends BaseRepository implements VideoRepositoryContract
{
    public function getFieldsSearchable()
    {
        return [];
    }

    public function model()
    {
        return Video::class;
    }
}
