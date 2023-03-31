<?php

namespace EscolaLms\Video\Strategies;

class VideoStrategyResourceContext
{
    private VideoResourceStrategy $strategy;

    public function __construct()
    {
        $this->strategy = $this->resolve();
    }

    private function resolve(): VideoResourceStrategy
    {
        if (config('escolalms_video.non_strict_value')) {
            return new VideoNonStrictValueResourceStrategy();
        }
        if (config('escolalms_video.enable')) {
            return new VideoEnableProcessingStrategy();
        }

        return new VideoResourceStrategy();
    }

    public function getStrategy(): VideoResourceStrategy
    {
        return $this->strategy;
    }
}
