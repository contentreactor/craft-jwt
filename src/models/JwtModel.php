<?php

namespace contentreactor\jwt\models;

use craft\base\Model;

/**
 * JwtTokenModel represents a model for JWT tokens.
 */
class JwtModel extends Model
{
    /** @var int|null The user ID associated with the token. */
    public $userId;

    /** @var string|null The JWT token string. */
    public $token;

    /** @var string|null The expiration date of the token. */
    public $expiration_date;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['userId', 'token', 'expiration_date'], 'required'],
            [['userId'], 'integer'],
            [['token'], 'string', 'max' => 255],
        ]);
    }
}
