<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Bookmarks Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TagsTable|\Cake\ORM\Association\BelongsToMany $Tags
 *
 * @method \App\Model\Entity\Bookmark get($primaryKey, $options = [])
 * @method \App\Model\Entity\Bookmark newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Bookmark[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bookmark|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bookmark patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BookmarksTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('bookmarks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsToMany('Tags', [
            'foreignKey' => 'bookmark_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'bookmarks_tags'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->allowEmpty('title');

        $validator
            ->scalar('description')
            ->allowEmpty('description');

        $validator
            ->scalar('url')
            ->allowEmpty('url');

        return $validator;
    }

    /**
     * 保存の前にデータの調整を行う
     * @param $event
     * @param $entity
     * @param $options
     */
    public function beforeSave($event, $entity, $options) {
        // カンマ区切りのタグ文字を、TagsEntity配列に変換。既存のデータ＋新規のデータ
        if($entity->tag_string) {
            $entity->tags = $this->_buildTags($entity->tag_string);
        }
    }

    /**
     * カンマ区切りの文字列を、TagsEntity配列に戻す
     * DBの既存の項目と一致するものはそのまま、新しいタグは新規のEntityとして追加して、返す
     * @param $tagString
     * @return array
     */
    protected function _buildTags($tagString) {
        // trim
        $newTags = array_map('trim', explode(',', $tagString));
        // remove empty tag
        $newTags = array_filter($newTags);
        // distinct
        $newTags = array_unique($newTags);

        $out = [];
        $query = $this->Tags->find()
            ->where(['Tags.title IN' => $newTags]);

        // 新しいタグの一覧から既存のタグを削除
        foreach($query->extract('title') as $existing) {
            $index = array_search($existing, $newTags);
            if($index !== false) {
                unset($newTags[$index]);
            }
        }
        // 既存のタグの追加
        foreach($query as $tag) {
            $out[] = $tag;
        }
        // 新しいタグの追加
        foreacH($newTags as $tag) {
            $out[] = $this->Tags->newEntity(['title' => $tag]);
        }
        return $out;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * タグ指定で、ブックマークを絞り込んで返す。
     * @param Query $query クエリービルダーオブジェクト
     * @param array $options 絞り込み条件のタグの配列
     * @return Query 絞り込み結果のクエリービルダーオブジェクト
     */
    public function findTagged(Query $query, array $options) {
        $bookmarks = $this->find()
            ->contain('Tags')
            ->select(['id', 'url', 'title', 'description']);
        if(empty($options['tags'])) {
            $bookmarks
                ->leftJoinWith('Tags')
                ->where(['Tags.title IS' => null]);
        } else {
            $bookmarks
                ->innerJoinWith('Tags')
                ->where(['Tags.title IN' => $options['tags']]);
        }

        return $bookmarks->group(['Bookmarks.id']);
    }
}
