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

    /**
     * Parses the given JWT token and returns the user ID extracted from it.
     *
     * @param string $token The JWT token to parse.
     * @return int|null The user ID extracted from the token, or null if parsing fails.
     * @throws UnsupportedHeaderFound If the token parsing fails due to an unsupported header.
     */
    public function parseToken(string $token): ?int
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

    /**
     * Checks if the current request path contains "api".
     *
     * @return bool Whether the current request path contains "api".
     */
    public function isApiPath(): bool
    {
        $paths = explode('/', Craft::$app->getRequest()->getPathInfo());
        return in_array('api', $paths);
    }

    /**
     * Checks if the user has permission to access the ContentReactor API.
     *
     * @param int $userId The ID of the user to check permissions for.
     * @return bool Whether the user has permission to access the ContentReactor API.
     */
    public function userHavePermission(int $userId): bool
    {
        $user = User::find()->id($userId)->one();
        return $this->_searchValue('contentReactorApi', $user->getGroups(), 'handle');
    }

    /**
     * Searches for a specific value within a multidimensional array.
     *
     * @param mixed $value The value to search for.
     * @param array $array The array to search in.
     * @param string $key The key to compare the value against.
     * @return bool Whether the value was found in the array.
     */
    private function _searchValue($value, $array, $key): bool
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
