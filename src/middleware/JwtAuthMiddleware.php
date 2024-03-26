<?php

namespace contentreactor\jwt\middleware;

use contentreactor\jwt\Plugin;
use contentreactor\jwt\records\JwtRecord;
use Craft;
use craft\elements\User;
use yii\base\ActionFilter;

/**
 * JwtAuthMiddleware is a Yii2 action filter for JWT authentication.
 */
class JwtAuthMiddleware extends ActionFilter
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();

        if (!$this->authorize($request)) {
            $response->setStatusCode(401);
            $response->content = 'Unauthorized';
            $response->send();
            Craft::$app->end();
            return false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Authorize the request based on the JWT token.
     * @param \yii\web\Request $request the request component
     * @return bool whether the request is authorized
     */
    private function authorize($request): bool
    {
        $headerToken = Plugin::getInstance()->getToken()->headerToken($request->headers['authorization']);
        $tokenExists = JwtRecord::find()->where(['=', 'token', $headerToken])->exists();
        if ($tokenExists) {
            $userIdFromToken = Plugin::getInstance()->getAuth()->parseToken($headerToken);
            $user = User::find()->id($userIdFromToken)->one();
            $haveGroup = Plugin::getInstance()->getAuth()->userHavePermission($user->id);
            if ($user->can("jwt-use-api") && $haveGroup) {
                return true;
            }
        } else {
            return false;
        }
    }
}
