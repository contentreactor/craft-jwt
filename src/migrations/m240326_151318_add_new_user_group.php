<?php

namespace contentreactor\jwt\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240326_151318_add_new_user_group migration.
 */
class m240326_151318_add_new_user_group extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->insert(Table::USERGROUPS, [
            'name' => 'ContentReactor API',
            'handle' => 'contentReactorApi',
            'description' => 'Add users to use API',
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->delete(Table::USERGROUPS, ['handle' => 'newUserGroup']);
        return true;
    }
}
