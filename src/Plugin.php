<?php

namespace dukt\videos\dailymotion;

class Plugin extends \craft\base\Plugin
{
    // Public Methods
    // =========================================================================

    /**
     * Get Videos gateways
     */
    public function getVideosGateways()
    {
        return [
            'dukt\videos\dailymotion\gateways\Dailymotion',
        ];
    }
}