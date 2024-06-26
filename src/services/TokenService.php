<?php

namespace contentreactor\jwt\services;

use DateTimeImmutable;
use DateTimeInterface;
use yii\base\Component;
use contentreactor\jwt\models\JwtModel;
use contentreactor\jwt\records\JwtRecord;
use Craft;
use craft\elements\User;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use yii\base\InvalidConfigException;

/**
 * TokenService handles operations related to JWT tokens.
 */
class TokenService extends Component
{
    /**
     * The environment variable key for the JWT secret key.
     * @var string
     */
    const ENV_SECRET_KEY = 'JWT_SECRET_KEY';

    /**
     * The environment variable key for the JWT ID.
     * @var string
     */
    const ENV_ID = 'JWT_ID';

    /**
     * The environment variable key for the JWT expiration time.
     * @var string
     */
    const ENV_EXPIRE = 'JWT_EXPIRE';

    /**
     * The environment variable key for the JWT request time.
     * @var string
     */
    const ENV_REQUEST_TIME = 'JWT_REQUEST_TIME';

    /**
     * The environment variable key for the primary site URL.
     * @var string
     */
    const ENV_PRIMARY_SITE_URL = 'PRIMARY_SITE_URL';

    /**
     * Saves a JWT token for a user.
     *
     * @param int $userId The ID of the user.
     * @param string $token The JWT token string.
     * @param DateTimeInterface $expirationDate The expiration date and time of the token.
     * @return void
     */
    public function saveTokenForUser(int $userId, string $token, DateTimeInterface $expirationDate)
    {
        $user = User::findOne($userId);

        if (!$user) {
            throw new \Exception("User with ID $userId doesn't exist.");
        }

        $jwtModel = new JwtModel();
        $jwtModel->userId = $user->id;
        $jwtModel->token = $token;
        $jwtModel->expiration_date = $expirationDate;

        $this->_saveToken($jwtModel);
    }

    /**
     * Saves a JWT token to the database.
     *
     * @param JwtModel $jwtModel The JWT model containing token data.
     *
     * @return bool Whether the token was successfully saved.
     * @throws InvalidConfigException If an error occurs while saving the token.
     */
    private function _saveToken(JwtModel $jwtModel): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $jwtToken = new JwtRecord();
            $jwtToken->user_id = $jwtModel->userId;
            $jwtToken->token = $jwtModel->token;
            $jwtToken->expiration_date = $jwtModel->expiration_date;
            if ($jwtToken->validate()) {
                $jwtToken->save();
            }
            $transaction->commit();
        } catch (\Throwable $th) {
            $transaction->rollBack();
            throw new InvalidConfigException($th);
        }
        return true;
    }

    /**
     * Retrieves the JWT token for a user.
     *
     * @param int $userId The ID of the user.
     * @return JwtRecord|null The JWT token for the user, or null if not found.
     */
    public function getTokenForUser(int $userId): ?JwtRecord
    {
        /** @var JwtRecord|null $token */
        return JwtRecord::findByUserId($userId);
    }

    /**
     * Validates a JWT token.
     *
     * @param string $tokenString The JWT token string.
     * @param string $secretKey The secret key used to sign the token.
     * @return bool Whether the token is valid.
     */
    public function validateToken(string $token): bool|InvalidConfigException
    {
        // Decode and verify the token
        $parser       = new Parser(new JoseEncoder());
        $algorithm    = new Sha256();
        $signingKey   = InMemory::plainText(getenv(self::ENV_SECRET_KEY));

        $token = $parser->parse($token);

        $validator = new Validator();

        try {
            $validator->assert($token, new IssuedBy(getenv(self::ENV_PRIMARY_SITE_URL)));
            $validator->assert($token, new SignedWith($algorithm, $signingKey));
            return true;
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidConfigException("Invalid configuration: " . $e);
        }
    }

    /**
     * Generates a JWT token for the given user.
     *
     * @param mixed $user The user object for whom the token is generated.
     *
     * @return Plain The generated JWT token.
     */
    public function generateJwt($user): Plain
    {
        $pluginSettings = Craft::$app->getProjectConfig()->get('contentreactor-jwt');
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm    = new Sha256();
        $signingKey   = InMemory::plainText(getenv($this->replaceKey($pluginSettings['jwtSecretKey'])) ?? getenv(self::ENV_SECRET_KEY));

        $now   = new DateTimeImmutable();

        $token = $tokenBuilder
            ->issuedBy(getenv(self::ENV_PRIMARY_SITE_URL))
            ->permittedFor(getenv(self::ENV_PRIMARY_SITE_URL))
            ->identifiedBy(getenv($this->replaceKey($pluginSettings['jwtId'])) ?? getenv(self::ENV_ID), true)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->modify(getenv($this->replaceKey($pluginSettings['jwtRequestTime'])) ?? getenv(self::ENV_REQUEST_TIME)))
            ->expiresAt($now->modify(getenv($this->replaceKey($pluginSettings['jwtExpire'])) ?? getenv(self::ENV_EXPIRE)))
            ->withClaim('uid', $user->id)
            ->withClaim('email', $user->email)
            ->getToken($algorithm, $signingKey);
        return $token;
    }

    /**
     * Extracts the token string from an Authorization header.
     * @param string $token The Authorization header value
     * @return string The extracted token string
     */
    public function headerToken($token): string
    {
        return str_replace(" ", "", ltrim($token, 'Bearer'));
    }

    public function replaceKey($key): string {
        return str_replace("$", "", $key);
    }
}
