<?php

namespace contentreactor\jwt\services;

use contentreactor\jwt\records\JwtRecord;
use Craft;
use craft\elements\User;
use craft\events\FindLoginUserEvent;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use yii\base\Component;
use yii\web\UnauthorizedHttpException;

/**
 * AuthService provides methods for authentication-related functionality.
 */
class AuthService extends Component
{
    /**
     * Finds a user by login name (username or email).
     *
     * This method first triggers a `FindLoginUserEvent` event to allow plugins to customize the
     * user lookup process. If the event does not return a user, it falls back to the default
     * Craft CMS method for finding a user by username or email.
     *
     * @param string $loginName The login name (username or email) of the user.
     * @return User|null The found user, or null if no user is found.
     */
    public function findLoginUser(string $loginName): ?User
    {
        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
        ]);

        $user = $event->user ?? Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
            'user' => $user,
        ]);

        return $event->user;
    }

    /**
     * Validates a JWT token for a specific user.
     *
     * @param string $token The JWT token to validate.
     * @param int $userId The ID of the user to verify token ownership.
     * @return User|null The user associated with the token, or null if the token is invalid or does not belong to the specified user.
     * @throws UnauthorizedHttpException If the token is invalid or does not belong to the specified user.
     */
    public function validateTokenForUser(string $token): ?User
    {
        $jwtTokenRecord = JwtRecord::find()->where(['token' => $token])->one();

        $userId = $this->parseToken($jwtTokenRecord->token);

        if (!$jwtTokenRecord || !$this->_isTokenValid($jwtTokenRecord)) {
            return null;
        }
        if ($jwtTokenRecord->user_id !== $userId) {
            throw new UnauthorizedHttpException('Token does not belong to the specified user.');
        }

        return Craft::$app->getUsers()->getUserById($userId);
    }

    /**
     * Checks if a JWT token record is valid (not expired).
     *
     * @param JwtRecord $jwtTokenRecord The JWT token record to validate.
     * @return bool Whether the token is valid.
     */
    private function _isTokenValid(JwtRecord $jwtTokenRecord): bool
    {
        $expirationDate = strtotime($jwtTokenRecord->expiration_date);
        return $expirationDate > time();
    }

    public function parseToken(string $token)
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($token);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            throw new UnsupportedHeaderFound('Oh no, an error: ' . $e->getMessage());
        }
        assert($token instanceof UnencryptedToken);

        return $token->claims()->get('uid');
    }

    public function isApiPath()
    {
        $paths = explode('/', Craft::$app->getRequest()->getPathInfo());
        return in_array('api', $paths);
    }

    public function userHavePermission(int $userId)
    {
        $user = User::find()->id($userId)->one();
        return $this->_searchValue('contentReactorApi', $user->getGroups(), 'handle');
    }

    private function _searchValue($value, $array, $key)
    {
        $found = false;

        array_walk_recursive($array, function ($item) use ($value, &$found, &$key) {
            if ($item->$key === $value) {
                $found = true;
            }
        });

        return $found;
    }
}
