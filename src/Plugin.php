<?php

namespace contentreactor\jwt;

use contentreactor\jwt\middleware\JwtAuthMiddleware;
use contentreactor\jwt\models\JwtSettingsModel;
use contentreactor\jwt\services\AuthService;
use Craft;
use contentreactor\jwt\services\TokenService;
use contentreactor\jwt\traits\Services;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * contentreactor-jwt plugin
 *
 * @method static Plugin getInstance()
 * @author Contentreactor
 * @copyright Contentreactor
 * @license MIT
 * @property-read AuthService $authService
 * @property-read TokenService $tokenService
 */
class Plugin extends BasePlugin
{
    use Services;

    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
                'authService' => AuthService::class,
                'tokenService' => TokenService::class
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->_routing();
            $this->_middleware();
            $this->_permissions();
            $this->_navigation();
        }
    }

    protected function _routing()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event): void {
                $event->rules['POST api/auth'] = "contentreactor-jwt/jwt/login";
            }
        );
    }

    protected function _middleware()
    {
        if (
            Plugin::getInstance()->getAuth()->isApiPath() &&
            Craft::$app->getRequest()->getPathInfo() !== 'api/auth'
        ) {
            Event::on(
                Application::class,
                Application::EVENT_BEFORE_ACTION,
                function ($event) {
                    $app = Craft::$app;
                    $app->controller->attachBehavior('authMiddleware', JwtAuthMiddleware::class);
                }
            );
        }
    }

    protected function _permissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $jwtPermissions = [];

                $jwtPermissions['jwt-use-api'] = [
                    'label' => Craft::t($this->id, 'Use API with JWT auth'),
                ];

                $event->permissions[] = [
                    'heading' => Craft::t($this->id, 'ContentReactor JWT'),
                    'permissions' => $jwtPermissions,
                ];
            }
        );
    }

    protected function _navigation()
    {
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $event->navItems[] = [
                    'url' => 'contentreactor-jwt/settings',
                    'label' => 'ContentReactor JWT API',
                    'icon' => '@yourplugin/icon.svg',
                ];
            }
        );
    }
}
