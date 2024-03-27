<?php

namespace contentreactor\jwt\controllers;

use DateTimeImmutable;
use contentreactor\jwt\services\AuthService;
use contentreactor\jwt\services\Jwt;
use contentreactor\jwt\services\TokenService;
use craft\elements\User;
use yii\base\InvalidConfigException;
use yii\base\Response;
use yii\rest\Controller;

/**
 * AuthController handles authentication-related actions.
 */
class JwtController extends Controller
{
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
     * Constructor.
     *
     * @param string $id
     * @param mixed $module
     * @param JWT $jwtService The auth service instance.
     * @param array $config
     */
    public function __construct(
        string $id,
        $module,
        AuthService $authService,
        TokenService $tokenService,
        array $congig = []
    ) {
        $this->authService = $authService;
        $this->tokenService = $tokenService;
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
        $loginName = $this->request->getRequiredBodyParam('loginName');
        $password = $this->request->getRequiredBodyParam('password');
        $user = $_authService->findLoginUser($loginName);

        if (!$user->authenticate($password)) {
            return throw new InvalidConfigException('Invalid credentials');
        }

        $token = $this->_createOrRead($user);

        return $this->asJson(['token' => $token]);
    }

    /**
     * Creates or reads the token for the user
     * @param \craft\elements\User $user The user object
     * @return string The generated or existing token
     */
    public function _createOrRead(User $user): string
    {
        $_tokenService = $this->tokenService;
        $tokenRecord = $_tokenService->getTokenForUser($user->id)->token ?? null;

        if (strlen($tokenRecord) > 1) {
            $token = $_tokenService->getTokenForUser($user->id)->token;
        } else {
            $token = $_tokenService->generateJwt($user)->toString();
            if ($_tokenService->validateToken($token)) {
                $user = $_tokenService->saveTokenForUser(
                    $user->id,
                    $token,
                    new DateTimeImmutable(
                        getenv(TokenService::ENV_EXPIRE)
                    )
                );
            }
        }

        return $token;
    }
}
