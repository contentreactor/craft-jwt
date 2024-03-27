<?php

namespace contentreactor\jwt\controllers;

use contentreactor\jwt\models\JwtSettingsModel;
use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * Settings Controller controller
 */
class SettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'index';

    /**
     * @inheritdoc
     */
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * Displays the settings page.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $settings = $this->getSettingsModel();
        return $this->renderTemplate('contentreactor-jwt/settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Retrieves the plugin settings model.
     *
     * @return JwtSettingsModel The plugin settings model.
     */
    private function getSettingsModel(): JwtSettingsModel
    {
        $plugin = Craft::$app->plugins->getPlugin('contentreactor-jwt');
        return $plugin->getSettings();
    }

    /**
     * Saves the plugin settings.
     *
     * @return Response The response to be sent.
     */
    public function actionSaveSettings(): Response
    {
        $this->requirePostRequest();
        $params = Craft::$app->getRequest()->getBodyParams();

        $settingsForm = new JwtSettingsModel();
        $settingsForm->jwtSecretKey = $params['jwtSecretKey'];
        $settingsForm->jwtId = $params['jwtId'];
        $settingsForm->jwtExpire = $params['jwtExpire'];
        $settingsForm->jwtRequestTime = $params['jwtRequestTime'];

        Craft::$app->getProjectConfig()->set('contentreactor-jwt', $settingsForm->toArray(), Craft::t('contentreactor-jwt', 'Update ContentReactor JWT settings.'));

        $this->setSuccessFlash(Craft::t('contentreactor-jwt', 'ContentReactor JWT API settings saved'));
        return $this->redirectToPostedUrl();
    }
}
