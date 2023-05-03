<?php

namespace mangochutney\blitzfastly;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\View;
use Fastly\Configuration;
use Fastly\Api\PurgeApi;
use GuzzleHttp;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\drivers\purgers\BaseCachePurger;
use putyourlightson\blitz\events\RefreshCacheEvent;
use putyourlightson\blitz\helpers\SiteUriHelper;
use yii\base\Event;

class FastlyPurger extends BaseCachePurger
{
    public string $apiKey = '';

    public string $serviceId = '';

    public static function displayName(): string
    {
        return 'Fastly Purger';
    }

    public function init(): void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['blitz-fastly'] = __DIR__ . '/templates/';
            }
        );
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
                'serviceId',
            ],
        ];

        return $behaviors;
    }

    public function attributeLabels(): array
    {
        return [
            'apiKey' => 'API Key',
            'serviceId' => 'Service ID'
        ];
    }

    public function rules(): array
    {
        return [
            [['apiKey', 'serviceId'], 'required'],
        ];
    }

    public function purgeUrisWithProgress(array $siteUris, callable $setProgressHandler = null): void
    {
        if (empty($siteUris)) {
            return;
        }

        $urls = SiteUriHelper::getUrlsFromSiteUris($siteUris);

        $count = 0;
        $total = count($urls);
        $label = 'Purging {total} pages.';

        if (is_callable($setProgressHandler)) {
            $progressLabel = Craft::t('blitz', $label, ['count' => $count, 'total' => $total]);
            call_user_func($setProgressHandler, $count, $total, $progressLabel);
        }

        foreach ($urls as $url) {
            $options['fastly_soft_purge'] = 1;
            $options['cached_url'] = $url;

            try {
                $this->_apiClient()->purgeSingleUrl($options);
            } catch(\Exception $e) {
                Blitz::$plugin->log($e->getMessage());
            }
        }

        $count = $total;

        if (is_callable($setProgressHandler)) {
            $progressLabel = Craft::t('blitz', $label, ['count' => $count, 'total' => $total]);
            call_user_func($setProgressHandler, $count, $total, $progressLabel);
        }
    }

    public function purgeAll(callable $setProgressHandler = null, bool $queue = true): void
    {
        $event = new RefreshCacheEvent();
        $this->trigger(self::EVENT_BEFORE_PURGE_ALL_CACHE, $event);

        if (!$event->isValid) {
            return;
        }

        $options['service_id'] = App::parseEnv($this->serviceId);

        try {
            $this->_apiClient()->purgeAll($options);
        } catch(\Exception $e) {
            Blitz::$plugin->log($e->getMessage());
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_PURGE_ALL_CACHE)) {
            $this->trigger(self::EVENT_AFTER_PURGE_ALL_CACHE, $event);
        }
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('blitz-fastly/settings', [
            'purger' => $this,
        ]);
    }

    private function _apiClient()
    {
        $client = new PurgeApi(
            new GuzzleHttp\Client(),
            Configuration::getDefaultConfiguration()->setApiToken(App::parseEnv($this->apiKey))
        );

        return $client;
    }
}
