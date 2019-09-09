<?php

namespace Encore\LoginCheckSafe\Http\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;

class AdminCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Admin::user()&&!$this->shouldPassThrough($request)) {
            //验证账号是否被禁用
            if(Admin::user()->enabled==0){
                return $this->loginOut($request,trans('auth.admindisabled'));
            }

            if(config('admin.extensions.login-check-safe.limit_one_login')===true){
                //验证账号是否在其他地方登录
                if(md5(Admin::user()->id.Admin::user()->login_at)!==$request->session()->get('admin_check.key')){
                    return $this->loginOut($request,trans('auth.admin_other_login'));
                }
            }

            //验证验证活跃时长,只有当配置的时候才开启，判断
            if (config('admin.extensions.login-check-safe.auto-out-sec')&&config('admin.extensions.login-check-safe.auto-out-sec')>0){
                $time_length = config('admin.extensions.login-check-safe.auto-out-sec')+$request->session()->get('admin_check.time')-now()->timestamp;
                if($time_length<0){
                    return $this->loginOut($request,trans('auth.admin_overtime'));
                }
            }
            $request->session()->put('admin_check.time',now()->timestamp);

        }


        return $next($request);
    }

    /**
     * 退出
     * @param $request
     * @param string $msg
     * @return \Illuminate\Http\RedirectResponse
     * @author liujt 2019/8/14 14:02
     */
    protected function loginOut($request,$msg=''){
        $redirectTo = admin_base_path(config('admin.auth.redirect_to', 'auth/login'));
        Admin::guard()->logout();
        $request->session()->invalidate();
        return redirect()->guest($redirectTo)->withInput()->withErrors(['username'=>$msg]);
    }
    protected function shouldPassThrough($request)
    {
        $excepts = config('admin.auth.excepts', [
            'auth/login',
            'auth/logout',
        ]);

        return collect($excepts)
            ->map('admin_base_path')
            ->contains(function ($except) use ($request) {
                if ($except !== '/') {
                    $except = trim($except, '/');
                }

                return $request->is($except);
            });
    }
}
