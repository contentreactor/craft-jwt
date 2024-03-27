<?php

namespace contentreactor\jwt\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240325_121957_create_jwt_toknes_table migration.
 */
class m240325_121960_create_jwt_toknes_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->archiveTableIfExists(Install::TABLE_JWT_TOKENS);
        $this->createTable(Install::TABLE_JWT_TOKENS, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'token' => $this->text(),
            'expiration_date' => $this->dateTime(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
        ]);

        $this->createIndex(null, Install::TABLE_JWT_TOKENS, ['token'], true);
        $this->addForeignKey(null, Install::TABLE_JWT_TOKENS, ['user_id'], Table::USERS, ['id'], 'CASCADE');

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
