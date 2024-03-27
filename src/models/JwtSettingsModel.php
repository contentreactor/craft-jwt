<?php

namespace contentreactor\jwt\models;

use Craft;
use craft\base\Model;

/**
 * Jwt Settings model
 */
class JwtSettingsModel extends Model
{
    /** @var string The JWT secret key. */
    public $jwtSecretKey;

    /** @var string The JWT ID. */
    public $jwtId;

    /** @var string The JWT expiration time. */
    public $jwtExpire;

     /** @var string The JWT request time. */
    public $jwtRequestTime;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['jwtSecretKey', 'jwtId', 'jwtExpire', 'jwtRequestTime'], 'required'],
            [['jwtSecretKey', 'jwtId', 'jwtExpire', 'jwtRequestTime'], 'string'],
        ];
    }
}
