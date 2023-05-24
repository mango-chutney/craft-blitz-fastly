<?php

namespace mangochutney\blitzfastly;

use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use mangochutney\blitzfastly\FastlyPurger;
use putyourlightson\blitz\helpers\CachePurgerHelper;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public function init()
    {
        parent::init();

        Event::on(
            CachePurgerHelper::class,
            CachePurgerHelper::EVENT_REGISTER_PURGER_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = FastlyPurger::class;
            }
        );
    }
}
