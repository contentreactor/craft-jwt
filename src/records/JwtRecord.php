<?php

namespace contentreactor\jwt\records;

use contentreactor\jwt\migrations\Install;
use craft\db\ActiveRecord;

/**
 * @property int $userId
 * @property string $token
 * @property int $expiration_date
 */
class JwtRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Install::TABLE_JWT_TOKENS;
    }

    /**
     * Finds a token by user ID.
     *
     * @param int $userId The ID of the user.
     * @return JwtRecord|null The token model, or null if not found.
     */
    public static function findByUserId(int $userId): ?JwtRecord
    {
        return static::findOne(['user_id' => $userId]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'token', 'expiration_date'], 'required'],
            [['user_id'], 'integer'],
            [['token'], 'string'],
            [['expiration_date'], 'safe'],
        ];
    }
}
