<?php

namespace contentreactor\jwt\controllers;

use Craft;
use craft\web\Controller;

/**
 * Settings Controller controller
 */
class SettingsController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public function actionIndex()
    {
        $settings = $this->getSettingsModel();
        return $this->renderTemplate('contentreactor-jwt/settings', [
            'settings' => $settings
        ]);
    }

    private function getSettingsModel()
    {
        $plugin = Craft::$app->plugins->getPlugin('contentreactor-jwt');
        return $plugin->getSettings();
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $settings = $this->getSettingsModel();
        $settings->load(Craft::$app->getRequest()->post());
        $plugin = Craft::$app->plugins->getPlugin('contentreactor-jwt');
        Craft::$app->plugins->savePluginSettings($plugin, $settings->toArray());
        Craft::$app->getSession()->setNotice('Settings saved.');
        return $this->redirectToPostedUrl();
    }
}
