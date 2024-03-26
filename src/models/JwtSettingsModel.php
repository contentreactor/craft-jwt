<?php

namespace contentreactor\jwt\models;

use Craft;
use craft\base\Model;

/**
 * Jwt Settings model
 */
class JwtSettingsModel extends Model
{
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }
}
