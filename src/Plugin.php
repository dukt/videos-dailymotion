<?php

namespace dukt\videos\dailymotion;

class Plugin extends \craft\base\Plugin
{
    // Public Methods
    // =========================================================================

    /**
     * Get OAuth Providers
     */
    public function getVideosGateways()
    {
        return [
            'dukt\videos\dailymotion\gateways\Dailymotion',
        ];
    }
}