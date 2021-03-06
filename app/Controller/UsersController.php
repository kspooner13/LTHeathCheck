<?php

App::uses('CakeSession', 'Model/Datasource');
App::uses('Security', 'Utility');

class UsersController extends AppController {

    public $components = array('Session', 'Flash', 'RequestHandler');
    public static $sessionKey = 'Auth.User';

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('login', 'add');
    }

    public function login() {
        //
        // Let's set the DataSource shall we?
        if (!empty($this->Session->read('database') || !empty($this->request->data['user']['database']))) {
            $this->User->setDataSource($this->Session->read('database'));
            $this->User->setDataSource($this->request->data['user']['database']);
        } else {
            $this->User->setDataSource('default');
        }

        //if already logged-in, redirect
        if ($this->Session->check('Auth.user')) {
            $this->redirect(array('contoller' => 'dashboards', 'action' => 'index'));
        }
        $array = Configure::read('database');
        $this->set('dblist', $array);


        // if we get the post information, try to authenticate
        if ($this->request->is('post')) {
            $database = $this->request->data['user']['database'];
            $this->Session->write('Database', $database);
            $check = $this->User->query("SELECT userid, `name`, email, last_date, AES_DECRYPT(password, UserID) as hash FROM Users WHERE `name` = '" . $this->request->data['user']['username'] . "'");
            $hash = $check[0][0]['hash'];
            $email = $check[0]['Users']['email'];
            $user = $this->request->data['user']['username'];
            $id = $check[0]['Users']['userid'];
            if ($hash === $this->request->data['user']['password']) {
                $this->Session->renew();
                $this->Session->write(self::$sessionKey, $user);
                $this->Session->write('Username', $user);
                $this->Session->write('Email', $email);
                $this->Session->write('Userid', $id);
                $event = new CakeEvent('Auth.afterIdentify', $this, array('user' => $user));
                $this->redirect(array('controller' => 'dashboards', 'action' => 'index'));
            } else {
                $this->Session->setFlash(__('Invalid username or password'));
            }
        }
    }

    public function logout() {
        $this->Session->destroy();
        $this->redirect($this->Auth->logout());
        }

    public function profile($id) {


        if (!($user = $this->User->find('first', array(
            'conditions' => array('User.Userid' => $id))))) {
            throw new NotFoundException(__('Client not found'));
        }
        $this->set(compact('user'));
    }

}

?>