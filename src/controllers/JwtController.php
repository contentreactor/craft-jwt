<?php

namespace contentreactor\jwt\controllers;

use contentreactor\jwt\services\AuthService;
use contentreactor\jwt\services\TokenService;
use Craft;
use craft\elements\User;
use DateTime;
use Exception;
use yii\base\Response;
use yii\rest\Controller;

/**
 * AuthController handles authentication-related actions.
 */
class JwtController extends Controller
{
    /**
     * The environment variable key for the JWT expiration time.
     * @var string
     */
    const ENV_EXPIRE = 'JWT_EXPIRE';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'index';

    /**
     * @inheritdoc
     */
    protected array|int|bool $allowAnonymous = true;

    /**
     * @var AuthService The auth service instance.
     */
    private $authService;

    /**
     * @var TokenService The token service instance.
     */
    private $tokenService;

    /**
     * @var DateTime dateTime.
     */
    private $time;

    /**
     * Constructor.
     *
     * @param string $id
     * @param mixed $module
     * @param AuthService $authService The auth service instance.
     * @param TokenService $tokenService The auth service instance.
     * @param DateTime $time
     * @param array $config
     */
    public function __construct(
        string $id,
        $module,
        AuthService $authService,
        TokenService $tokenService,
        DateTime $time,
        array $congig = []
    ) {
        $this->authService = $authService;
        $this->tokenService = $tokenService;
        $this->time = $time;
        parent::__construct($id, $module, $congig);
    }

    /**
     * Login action
     * @return \yii\web\Response - returns the user token
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLogin(): Response
    {
        $_authService = $this->authService;

        try {
            $loginName = $this->request->getRequiredBodyParam('loginName');
            $password = $this->request->getRequiredBodyParam('password');
            $user = $_authService->findLoginUser($loginName);
            return $this->_validateUser($user, $password);
        } catch (Exception) {
            return $this->_invalidCredentials("Missing required body parameters.");
        }
    }

    /**
     * Creates or reads the token for the user
     * @param \craft\elements\User $user The user object
     * @return string The generated or existing token
     */
    private function _createOrRead(User $user): string
    {
        $_tokenService = $this->tokenService;
        $tokenRecord = $_tokenService->getTokenForUser($user->id)->token ?? null;
        $pluginSettings = Craft::$app->getProjectConfig()->get('contentreactor-jwt');

        if (strlen($tokenRecord) > 1) {
            $token = $_tokenService->getTokenForUser($user->id)->token;
        } else {
            $token = $_tokenService->generateJwt($user)->toString();
            if ($_tokenService->validateToken($token)) {
                $user = $_tokenService->saveTokenForUser(
                    $user->id,
                    $token,
                    $this->time->modify(
                        getenv($_tokenService->replaceKey($pluginSettings['jwtExpire'])) ??
                            getenv(self::ENV_EXPIRE)
                    )
                );
            }
        }
        return $token;
    }

    /**
     * Responds with a JSON-encoded message indicating invalid credentials
     *
     * @param string $message The message to be included in the response. Default is 'Invalid credentials.'
     * @return Response The response object with status code 401 and a JSON-encoded message.
     */
    private function _invalidCredentials($message = 'Invalid credentials.'): Response
    {
        Craft::$app->getResponse()->setStatusCode(401);;
        return $this->asJson(['message' => $message]);
    }

    /**
     * Validates user credentials and generates a token upon successful authentication.
     *
     * @param User|null $user The user object to validate. Null if user is not found.
     * @param string $password The password to authenticate the user.
     * @return Response|null The response object with a JSON-encoded token upon successful authentication,
     *                       or null if user is not found or authentication fails.
     */
    private function _validateUser(User|null $user, string $password): Response|null
    {
        if ($user) {
            if (!$user->authenticate($password)) {
                return $this->_invalidCredentials();
            }
            $token = $this->_createOrRead($user);
            return $this->asJson(['token' => $token]);
        } else {
            return $this->_invalidCredentials();
        }
    }
}
