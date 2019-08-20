<?php

namespace Encore\LoginCheckSafe\Http\Controllers;

use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Encore\Admin\Middleware\Session;
use Encore\LoginCheckSafe\Models\LoginLogModel;
use Encore\LoginCheckSafe\Models\PasswordLogModel;
use Encore\LoginCheckSafe\Rules\AdminPassword;
use Encore\LoginCheckSafe\Rules\AdminPasswordUsed;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Cache;

class LoginCheckSafeController extends Controller
{
    //对应后台用户密码错误次数的缓存KEY
    private $_login_error_num_cache_key = 'admin_user_error_num_';
    //锁定后不能登录的标识 缓存KEY
    private $_login_error_no_login_cache_key = 'admin_user_can_login_time_';

    public function login()
    {
        return view('login-check-safe::index');
    }

    public function postLogin(Request $request)
    {
        $credentials = $request->only(['username', 'password']);
        $userModel = config('admin.database.users_model');
        $validator = Validator::make($request->all(), [
            //'username' => "required|alpha_num|exists:{$connection}.{$userTable},username,enabled,1",
            'username' => "required|alpha_num",
            'password' => 'required',
            'captcha' => 'required|captcha'
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }
        //这里单独判断用户名是否存在及
        $user = $userModel::where('username',$request->post('username'))->first();
        if($user==null||!$user){
            return Redirect::back()->withInput()->withErrors(['username' => $this->getFailedLoginMessage()]);

        }
        //当被禁用的时候
        if($user->enabled == 0){
            LoginLogModel::create([
                'user_id' => $user->id,
                'state' => 0,
                'reason' => trans('auth.admindisabled')
            ]);
            return Redirect::back()->withInput()->withErrors(['username' => trans('auth.admindisabled')]);
        }
        //密码过期时间,密码过期验证，排除账号ID为1的用户；
        $pass_expried_time = config('admin.extensions.login-check-safe.password-expired',2592000) - (now()->timestamp - strtotime($user->pass_update_at));
        if($user->pass_update_at&&$pass_expried_time<=0&&$user->id!=1){
            LoginLogModel::create([
                'user_id' => $user->id,
                'state' => 0,
                'reason' => trans('auth.adminPassExpried')
            ]);
            return Redirect::back()->withInput()->withErrors(['password' => trans('auth.adminPassExpried')]);
        }
        //当错误次数达到限制次数并且还在锁定时间期限内，直接返回锁定提示
        $limit_num = config('admin.extensions.login-check-safe.login-error-num',5);
        $can_login_time = Cache::get($this->_login_error_no_login_cache_key.$user->id);
        $diff_time = $can_login_time - now()->timestamp;
        if($diff_time>0&&Cache::get($this->_login_error_num_cache_key.$user->id)>=$limit_num){
            LoginLogModel::create([
                'user_id' => $user->id,
                'state' => 0,
                'reason' => trans('auth.errorTooMuch',['num'=>$limit_num,'min'=>ceil($diff_time/60)])
            ]);
            return Redirect::back()->withInput()->withErrors(['password' => trans('auth.errorTooMuch',['num'=>$limit_num,'min'=>ceil($diff_time/60)])]);
        }
        if (Auth::guard('admin')->attempt($credentials)) {
            //登录成功清楚错误次数信息
            Cache::forget($this->_login_error_num_cache_key.$user->id);
            Cache::forget($this->_login_error_no_login_cache_key.$user->id);
            // 当密码有效期还在7天内时，进行登录成功后的提示。
            $other = '';
            $left_day = Carbon::createFromTimestamp($pass_expried_time+now()->timestamp)->diffForHumans();
            if(ceil($pass_expried_time/86400)<=7){
                $other = "您的密码将在 {$left_day} 过期，请该尽快修改密码";
            }
            LoginLogModel::create([
                'user_id' => $user->id,
                'state' => 1,
                'reason' => trans('auth.login_successful',['other'=>$other])
            ]);
            $login_time = now()->toDateTimeString();
            Administrator::where('id',$user->id)->update(['login_at'=>$login_time]);
            //设置一个验证session
            \Session::put(['admin_check'=>['key'=>md5(Admin::user()->id.$login_time),'time'=>strtotime($login_time)]]);
            admin_toastr(trans('auth.login_successful',['other'=>$other]));
            return redirect()->intended(config('admin.route.prefix'));
        }else{
            //密码错误次数
            //如果错误有效时间还大于当前时间，直接自增错误次数
            if(Cache::get($this->_login_error_no_login_cache_key.$user->id)>now()->timestamp){
                $num = Cache::increment($this->_login_error_num_cache_key.$user->id,1);
            }else{
                //如果错误有效时间还小于当前时间，直接重置错误次数
                $num = 1;
                Cache::set($this->_login_error_num_cache_key.$user->id,$num);
            }
            //当错误次数达到限制次数直接返回锁定提示
            $limit_sec = config('admin.extensions.login-check-safe.login-error-limit-sec',600);
            if($limit_num-$num<=0){
                LoginLogModel::create([
                    'user_id' => $user->id,
                    'state' => 0,
                    'reason' => trans('auth.errorTooMuch',['num'=>$limit_num,'min'=>ceil($limit_sec/60)])
                ]);
                return Redirect::back()->withInput()->withErrors(['password' => trans('auth.errorTooMuch',['num'=>$limit_num,'min'=>ceil($limit_sec/60)])]);
            }else{
                $reache_time = now()->timestamp+$limit_sec;
                Cache::set($this->_login_error_no_login_cache_key.$user->id,$reache_time,$reache_time);
                LoginLogModel::create([
                    'user_id' => $user->id,
                    'state' => 0,
                    'reason' => trans('auth.pass_failed',['num'=>$limit_num-$num])
                ]);
                return Redirect::back()->withInput()->withErrors(['password' => trans('auth.failed',['num'=>$limit_num-$num])]);
            }

        }


    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage()
    {
        return Lang::has('auth.failed')
            ? trans('auth.failed')
            : 'These credentials do not match our records.';
    }

    /**
     * User setting page.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function getSetting(Content $content)
    {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
                $tools->disableDelete();
                $tools->disableView();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->id));
    }

    /**
     * Update user setting.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSetting()
    {
        return $this->settingForm()->update(Admin::user()->id);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        $class = config('admin.database.users_model');

        $form = new Form(new $class());
        $form->display('username', trans('admin.username'));
        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));

        //修正重置密码的方式
        $form->divider();
        $form->password('new_password', trans('admins.new_password'))->rules(['confirmed',new AdminPassword(),new AdminPasswordUsed(Admin::user()->id)])->default('');
        $form->password('new_password_confirmation', trans('admin.password_confirmation'))->rules('')->default('');
        $form->password('old_password',trans('admins.old_password'))->rules()->help(trans('admins.old_password_help'));
        $form->setAction(admin_base_path('auth/setting'));

        $form->hidden('password');
        $form->hidden('passwordmd5');
        $form->hidden('pass_update_at');

        $form->ignore(['password','passwordmd5','pass_update_at','old_password','new_password','new_password_confirmation']);

        $form->saving(function ($form){
            //更新前处理
            $new_password = request()->input('new_password');//新密码
            $old_password = request()->input('old_password');//旧密码
            if ($new_password) {
                //这里需要判断如果修改密码那么必须填写旧密码
                if($old_password==''||$old_password==null){
                    return Redirect::back()->withInput()->withErrors(['old_password' => trans('admins.old_password_empty')]);
                }
                //验证老密码
                if(!Hash::check($old_password,$form->model()->password)){
                    return Redirect::back()->withInput()->withErrors(['old_password' => trans('admins.old_password_wrong')]);
                }
                //重置几个参数；
                $form->passwordmd5 = md5(config('app.key').$new_password);
                $form->password = bcrypt($new_password);
                $form->pass_update_at = now()->toDateTimeString();
            }
        });

        $form->saved(function (Form $form) {
            //写入密码修改日志
            if($form->passwordmd5){
                $passdata = [
                    'user_id' => $form->model()->id,
                    'password' => $form->model()->passwordmd5,
                ];
                PasswordLogModel::create($passdata);
            }
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(admin_base_path('auth/setting'));
        });

        return $form;
    }
}
