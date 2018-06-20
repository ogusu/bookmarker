<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Bookmarks Controller
 *
 * @property \App\Model\Table\BookmarksTable $Bookmarks
 *
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BookmarksController extends AppController
{
    /**
     * コントローラーの操作権限のチェック
     * @param $user
     * @return bool
     */
    public function isAuthorized($user)
    {
        $action = $this->request->getParam('action');

        // index, add, tabsは無条件に許可する
        if(in_array($action, ['index', 'add', 'tags'])) {
            return true;
        }
        // その他のすべてのアクションはIDを必要とする。
        if(!$this->request->getParam('pass.0')) {
            return false;
        }
        // 対象のブックマークが現在のログインユーザーの持ち物かをチェック
        $id = $this->request->getParam('pass.0');
        $bookmark = $this->Bookmarks->get($id);
        if($bookmark->user_id == $user['id']) {
            return true;
        }

        return parent::isAuthorized($user);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        // 現在ログインしているユーザーのブックマークだけを表示する
        $this->paginate = [
            //'contain' => ['Users']
            'conditions' => [
                'Bookmarks.user_id' => $this->Auth->user('id'),
            ]
        ];
        $this->set('bookmarks', $this->paginate($this->Bookmarks));
        $this->set('_serialize', ['bookmarks']);
    }

    /**
     * View method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Users', 'Tags']
        ]);

        $this->set('bookmark', $bookmark);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $bookmark = $this->Bookmarks->newEntity();
        if ($this->request->is('post')) {
            $bookmark = $this->Bookmarks->patchEntity($bookmark, $this->request->getData());
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmarks->save($bookmark)) {
                $this->Flash->success(__('ブックマークを保存しました。'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('ブックマークは保存できませんでした。もう一度お試しください。'));
        }
        $tags = $this->Bookmarks->Tags->find('list');
        $this->set(compact('bookmark', 'tags'));
        $this->set('_serialize', ['bookmark']);

        //$users = $this->Bookmarks->Users->find('list', ['limit' => 200]);
        //$tags = $this->Bookmarks->Tags->find('list', ['limit' => 200]);
        //$this->set(compact('bookmark', 'users', 'tags'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Tags']
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $bookmark = $this->Bookmarks->patchEntity($bookmark, $this->request->getData());
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmarks->save($bookmark)) {
                $this->Flash->success(__('ブックマークを保存しました。'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('ブックマークは保存できませんでした。もう一度お試しください。'));
        }
        $tags = $this->Bookmarks->Tags->find('list');
        $this->set(compact('bookmark', 'tags'));
        $this->set('_serialize', ['bookmark']);

        //$users = $this->Bookmarks->Users->find('list', ['limit' => 200]);
        //$tags = $this->Bookmarks->Tags->find('list', ['limit' => 200]);
        //$this->set(compact('bookmark', 'users', 'tags'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $bookmark = $this->Bookmarks->get($id);
        if ($this->Bookmarks->delete($bookmark)) {
            $this->Flash->success(__('The bookmark has been deleted.'));
        } else {
            $this->Flash->error(__('The bookmark could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * tag表示に対応
     */
    public function tags() {
        // CakePHP によって提供された'pass'キーは全てのリクエストにある渡されたURLのパスセグメント
        $tags = $this->request->getParam('pass');

        // タグ付きのブックマークを探すためにBookmarksTableを使用する。finderメソッド"tagged"を使用して絞り込む
        $bookmarks = $this->Bookmarks->find('tagged', ['tags' => $tags]);

        // ビューテンプレートに変数を渡す
        $this->set([
           'bookmarks' => $bookmarks,
            'tags' => $tags
        ]);
    }
}
