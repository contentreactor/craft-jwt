<?php
namespace contentreactor\jwt\migrations;

use Craft;
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
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

		return true;
	}

    /**
     * @inheritdoc
     */
	public function safeDown(): bool
	{
        $this->dropForeignKeys();
        $this->dropTables();
        $this->dropProjectConfig();
        return true;
	}

    public function createTables(): void {
        $this->archiveTableIfExists(self::TABLE_JWT_TOKENS);
		$this->createTable(self::TABLE_JWT_TOKENS, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->text()->notNull(),
            'expiration_date' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->dateTime()->notNull()->defaultValue(new \yii\db\Expression('NOW()')),
		]);
    }

    /**
     * Drop the tables
     */
    public function dropTables(): void
    {
        $tables = $this->_getAllTableNames();
        foreach ($tables as $table) {
            $this->dropTableIfExists($table);
        }
    }

    /**
     * Deletes the project config entry.
     */
    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove('contentreactor-jwt');
    }

    /**
     * Creates the indexes.
     */
    public function createIndexes(): void {
        $this->createIndex(null, self::TABLE_JWT_TOKENS, ['token'], true);
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys(): void {
        $this->addForeignKey(null, self::TABLE_JWT_TOKENS, ['user_id'], Table::USERS, ['id'], 'CASCADE');
    }

    /**
     * Removes the foreign keys.
     */
    public function dropForeignKeys(): void {
        $tables = $this->_getAllTableNames();

        foreach ($tables as $table) {
            $this->_dropForeignKeyToAndFromTable($table);
        }
    }

    /**
     * Insert the default data.
     */
    public function insertDefaultData(): void {
        $this->defaultUserGroup();

    }

    /**
     * Default user group for ContentReactor JWT API plugin.
     */
    private function defaultUserGroup(): void {
        $existingGroup = Craft::$app->userGroups->getGroupByHandle('contentReactorApi') ? true : false;

        if ($existingGroup) {
            $this->insert(Table::USERGROUPS, [
                'name' => 'ContentReactor API',
                'handle' => 'contentReactorApi',
                'description' => 'Add users to use API', 
            ]);
        }
    }
}