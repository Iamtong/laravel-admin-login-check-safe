<?php


namespace Encore\LoginCheckSafe\Actions\Post;


use Encore\Admin\Actions\RowAction;

class LogPassView extends RowAction  {
    public $name = '密码记录';
    public function href() {
        return admin_url('auth/passlogs').'?user_id='.$this->getKey();
    }
}
