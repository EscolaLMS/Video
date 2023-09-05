<?php

namespace EscolaLms\Video\Repositories\Contracts;

use EscolaLms\Core\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface VideoRepositoryContract extends BaseRepositoryContract
{
    public function getBetweenProcessDates(Carbon $dateTimeFrom, Carbon $dateTimeTo, string $state): Collection;
}
