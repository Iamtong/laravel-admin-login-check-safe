<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableEnabledIntoAdminUser extends Migration
{
    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //添加字段
        Schema::connection($this->getConnection())->table(config('admin.database.users_table'), function (Blueprint $table) {
            $table->timestamp('pass_update_at')->nullable()->comment('密码最后更新时间')->default(date('Y-m-d H:i:s'));
            $table->string('passwordmd5','32')->nullable()->comment('修改过的密码MD5');
            $table->boolean('enabled')->default(true)->comment('账号是否启用 1位启用');
            $table->timestamp('login_at')->nullable()->comment('最后登录时间');
        });
        //添加密码修改记录
        Schema::connection($this->getConnection())->create(config('admin.extensions.login-check-safe.db.password_log_table'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户ID');
            $table->string('password','32')->comment('修改过的密码MD5');
            $table->string('remark','128')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->index('updated_at');
        });
        //添加登录记录
        Schema::connection($this->getConnection())->create(config('admin.extensions.login-check-safe.db.login_log_table'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户ID');
            $table->boolean('state')->default(true)->comment('是否登录成功');
            $table->string('reason','48')->comment('登录失败原因');
            $table->timestamps();
            $table->index('user_id');
            $table->index('created_at');
        });
        //更新权限
        Encore\Admin\Auth\Database\Permission::where('id',5)->update(['http_path'=>'/auth/roles
/auth/permissions
/auth/menu
/auth/logs
/auth/loginlogs
/auth/passlogs']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->getConnection())->table(config('admin.database.users_table'), function (Blueprint $table) {
            $table->dropColumn('pass_update_at');
            $table->dropColumn('passwordmd5');
            $table->dropColumn('enabled');
            $table->dropColumn('login_at');
        });
        Schema::connection($this->getConnection())->dropIfExists(config('admin.extensions.login-check-safe.db.password_log_table'));
        Schema::connection($this->getConnection())->dropIfExists(config('admin.extensions.login-check-safe.db.login_log_table'));
    }
}
