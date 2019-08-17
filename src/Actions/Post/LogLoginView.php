<?php
namespace Encore\LoginCheckSafe\Actions\Post;

use Encore\Admin\Actions\RowAction;

class LogLoginView extends RowAction {
    public $name = '登录日志';
    public function href() {
        return admin_url('auth/loginlogs').'?user_id='.$this->getKey();
    }
}
