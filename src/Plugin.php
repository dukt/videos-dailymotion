<?php

namespace dukt\videos\dailymotion;

use dukt\videos\services\Gateways;
use yii\base\Event;

/**
 * Plugin represents the Dailymotion integration plugin.
 *
 * @author    Dukt <support@dukt.net>
 * @since     1.0
 */
class Plugin extends \craft\base\Plugin
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES, function($event): void {
            $gatewayTypes = [
                \dukt\videos\dailymotion\gateways\Dailymotion::class
            ];

            $event->gatewayTypes = array_merge($event->gatewayTypes, $gatewayTypes);
        });
    }
}