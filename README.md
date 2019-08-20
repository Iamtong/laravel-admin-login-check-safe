#laravel-admin extension
## laravel-admin-login-check-safe
###### 填加以下内容
* 登录验证码
* 登录达到配置次数，禁止登录一段时间（可配置）
* 账号密码有有效期，过期禁止登录（排除ID为 1 的管理员）
* 添加账号禁用，账号禁用后不能登录,如果账号正在使用，会被强制退出登录
* 修改密码添加修改记录
* 添加登录记录
* 限制账号同时使用
* 限制账号不活动达到配置时间后，自动退出

###### 执行步骤如下
* 在项目目录执行以下命令

```shell script
composer require iamtong/laravel-admin-login-check-safe
php artisan vendor:publish --provider=Encore\LoginCheckSafe\LoginCheckSafeServiceProvider
```


* 需要修改 config/admin.php
extensions里面添加
```php
        //登录验证码/用户锁定
        'login-check-safe' => [
            'enable' => true,
            'login-error-num' => 5,//登录允许密码错误的次数
            'login-error-limit-sec' => 600,//达到错误次数后锁定的时间（单位秒）
            'password-expired' => 90*86400,//密码过期时间 90 天（单位秒）
            'auto-out-sec' => 1800,//多久没活跃后，自动退出账号（单位秒）
            'username-rules' => 'regex:/^[a-zA-Z0-9]+$/i|between:4,40',//用户名除了唯一性和必须填写之外的所有规则
            'username-rules-msg' => [
                'regex' => '用户名必须以大小写字母和数字组成',
            ],//对应的提示方法
            'password-strong' => 2,// 【大写字母 小写字母 数字 特殊字符】 密码强度 必须使用其中的几种。
            'password-length' => '8,40',//密码长度范围 10,40 10到40位；
            'db' => [
                //密码修改纪录表
                'password_log_table' => 'admin_password_log',
                'password_log_model' =>Encore\LoginCheckSafe\Models\PasswordLogModel::class,
                //登录日志表
                'login_log_table' => 'admin_login_log',
                'login_log_model' =>Encore\LoginCheckSafe\Models\LoginLogModel::class,
            ]
        ],
```

执行数据库文件
```shell script
php artisan migrate
```

然后在 route.middleware里面添加
Encore\LoginCheckSafe\Http\Middleware\AdminCheck::class
如下：
```php
'middleware' => ['web', 'admin',Encore\LoginCheckSafe\Http\Middleware\AdminCheck::class],
```