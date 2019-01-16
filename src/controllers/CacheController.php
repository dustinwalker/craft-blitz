<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\jobs\WarmCacheJob;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class CacheController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Clears the cache.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionClear(): Response
    {
        Blitz::$plugin->invalidate->clearCache(false);

        Craft::$app->getSession()->setNotice(Craft::t('blitz', 'Blitz cache successfully cleared.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Flushes the cache.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionFlush(): Response
    {
        Blitz::$plugin->invalidate->clearCache(true);

        Craft::$app->getSession()->setNotice(Craft::t('blitz', 'Blitz cache successfully flushed.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Refreshes expired elements.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionRefreshExpired(): Response
    {
        Blitz::$plugin->invalidate->refreshExpiredCache();

        Craft::$app->getSession()->setNotice(Craft::t('blitz', 'Expired Blitz cache successfully refreshed.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Warms the cache.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws Exception
     */
    public function actionWarm(): Response
    {
        $settings = Blitz::$plugin->getSettings();

        if (!$settings->cachingEnabled) {
            Craft::$app->getSession()->setError(Craft::t('blitz', 'Blitz caching is disabled.'));

            return $this->redirectToPostedUrl();
        }

        // Get URLs before flushing the cache
        $urls = Blitz::$plugin->invalidate->getAllCachedUrls();

        Blitz::$plugin->invalidate->clearCache(true);

        Craft::$app->getQueue()->push(new WarmCacheJob(['urls' => $urls]));

        Craft::$app->getSession()->setNotice(Craft::t('blitz', 'Blitz cache successfully queued for warming.'));

        return $this->redirectToPostedUrl();
    }
}
