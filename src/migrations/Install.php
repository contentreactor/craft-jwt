<?php

namespace contentreactor\jwt\migrations;

use craft\db\Migration;
use craft\db\Table;

class Install extends Migration
{
    const TABLE_JWT_TOKENS = '{{%contentreactor_jwt_tokens}}';

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(self::TABLE_JWT_TOKENS, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->text()->notNull(),
            'expiration_date' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->dateTime()->notNull()->defaultValue(new \yii\db\Expression('NOW()')),
        ]);

        $this->createIndex(null, self::TABLE_JWT_TOKENS, ['token'], true);
        $this->addForeignKey(null, self::TABLE_JWT_TOKENS, ['user_id'], Table::USERS, ['id'], 'CASCADE');

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
        $this->dropTable(Install::TABLE_JWT_TOKENS);
        return true;
    }
}
