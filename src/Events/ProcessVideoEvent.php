<?php

namespace EscolaLms\Video\Events;

use EscolaLms\Courses\Models\Topic;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class ProcessVideoEvent
{
    use Dispatchable, SerializesModels;

    private Authenticatable $user;
    private Topic $topic;

    /**
     * @param Authenticatable $user
     * @param Topic $topic
     */
    public function __construct(Authenticatable $user, Topic $topic)
    {
        $this->user = $user;
        $this->topic = $topic;
    }

    /**
     * @return Topic
     */
    public function getTopic(): Topic
    {
        return $this->topic;
    }

    /**
     * @return Authenticatable
     */
    public function getUser(): Authenticatable
    {
        return $this->user;
    }
}
