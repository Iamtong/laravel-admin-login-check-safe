<?php

namespace Encore\LoginCheckSafe\Http\Controllers;

use Encore\LoginCheckSafe\Actions\Post\LogLoginView;
use Encore\LoginCheckSafe\Actions\Post\LogPassView;
use Encore\LoginCheckSafe\Models\PasswordLogModel;
use Encore\LoginCheckSafe\Rules\AdminPassword;
use Illuminate\Routing\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UserController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $pass_expried_time = config('admin.extensions.login-check-safe.password-expired',2592000) - (now()->timestamp - strtotime(Admin::user()->pass_update_at));
        $pass_day = ceil($pass_expried_time/86400);
        $left_day = Carbon::createFromTimestamp($pass_expried_time+now()->timestamp)->diffForHumans();
        $msg = trans("admins.password_expired",['num'=>$left_day]);
        return $content
            ->header(trans('admin.administrator'))
            ->description(trans('admin.list'))->row(function (Row $row) use ($msg,$pass_day) {
                if($pass_day<10) {
                    $row->column(12, function (Column $column) use ($msg) {
                        $column->append((new Widgets\Alert($msg))->style('warning')->icon('user'));
                    });
                }else{
                    $row->column(12, function (Column $column) use ($msg) {
                        $column->append((new Widgets\Alert($msg))->style('info')->icon('info'));
                    });
                }
            })
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header(trans('admin.administrator'))
            ->description(trans('admin.detail'))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header(trans('admin.administrator'))
            ->description(trans('admin.edit'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.administrator'))
            ->description(trans('admin.create'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $userModel = config('admin.database.users_model');

        $grid = new Grid(new $userModel());
        $grid->disableExport();
        $grid->id('ID')->sortable();
        $grid->username(trans('admin.username'));
        $grid->name(trans('admin.name'));
        $grid->roles(trans('admin.roles'))->pluck('name')->label();
        $states = [
            'off' => ['value' => 0, 'text' => trans('admins.disabled'), 'color' => 'default'],
            'on'  => ['value' => 1, 'text' => trans('admins.enabled'), 'color' => 'primary'],
        ];
        $grid->enabled(trans('admins.state'))->switch($states);
        $grid->created_at(trans('admin.created_at'));
        $grid->updated_at(trans('admin.updated_at'));
        $grid->pass_update_at(trans('admins.pass_update_at'));
        $grid->login_at(trans('admins.login_at'));
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            //添加两个日志链接
            $actions->add(new LogLoginView());
            $actions->add(new LogPassView());

        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        $grid->filter(function ($filter){
            $filter->like('username',trans('admin.username'));
            $filter->equal('enabled',trans('admins.state'))->radio([
                '' => trans('admin.all'),
                0 => trans('admins.disabled'),
                1 => trans('admins.enabled')
            ]);
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = config('admin.database.users_model');

        $show = new Show($userModel::findOrFail($id));

        $show->id('ID');
        $show->username(trans('admin.username'));
        $show->name(trans('admin.name'));
        $show->roles(trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        $show->permissions(trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));
        $show->pass_update_at(trans('admins.pass_update_at'));
        $show->login_at(trans('admins.login_at'));
        $show->enabled(trans('admins.state'))->using([
            1 => trans('admins.enabled'),
            0 => trans('admins.disabled'),
        ]);

        $show->panel()->tools(function ($tools){
            $tools->disableDelete();
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');
        $connect = config('admin.database.connection');
        $userTable = config('admin.database.users_table');


        $form = new Form(new $userModel());

        $form->tools(function (Form\Tools $tools){
            $tools->disableDelete();
        });
        $form->display('id', 'ID');
        if(\Route::currentRouteNamed('*edit*')){
            $form->display('username', trans('admin.username'));
        }else{
            $rules = 'required|unique:'.$connect.'.'.$userTable;
            if(config('admin.extensions.login-check-safe.username-rules')){
                $rules .= config('admin.extensions.login-check-safe.username-rules');
            }
            $form->text('username', trans('admin.username'))->rules($rules,config('admin.extensions.login-check-safe.username-rules-msg'));
        }

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'))->uniqueName();
        $passLength = '10,70';
        if(config('admin.extensions.login-check-safe.password-length')){
            $passLength = config('admin.extensions.login-check-safe.password-length');
        }
        $form->password('password', trans('admin.password'))->rules(['required','confirmed','between:'.$passLength,new AdminPassword()]);
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));
        $states = [
            'on'  => ['value' => 1, 'text' => trans('admins.enabled'), 'color' => 'success'],
            'off' => ['value' => 0, 'text' => trans('admins.disabled'), 'color' => 'danger'],
        ];
        $form->switch('enabled',trans('admins.state'))->states($states)->default(1);

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));
        if(\Route::currentRouteNamed('*edit*')){
            $form->display('pass_update_at', trans('admins.pass_update_at'))->default('');
        }
        $form->hidden('passwordmd5');
        $form->hidden('pass_update_at');
        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->passwordmd5 = md5(config('app.key').$form->password);
                $form->password = bcrypt($form->password);
                $form->pass_update_at = now()->toDateTimeString();
            }else{
                $form->ignore(['passwordmd5','pass_update_at']);
            }
        });
        $form->saved(function (Form $form){
            if($form->pass_update_at&&strtotime($form->pass_update_at)==now()->timestamp){
                //查询最近是否使用过
                $passdata = [
                    'user_id' => $form->model()->id,
                    'password' => $form->model()->passwordmd5,
                ];
                PasswordLogModel::create($passdata);
            }

        });
        //var_dump($form);exit;
        return $form;
    }


}
