<?php

namespace Encore\LoginCheckSafe\Http\Controllers;

use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\LoginCheckSafe\Models\LoginLogModel;
use Encore\LoginCheckSafe\Models\PasswordLogModel;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class LogPassController extends Controller
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
        return $content
            ->header(trans('admins.log_pass'))
            ->description('')
            ->body($this->grid());
    }
    /**
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PasswordLogModel());

        $grid->model()->orderBy('id', 'DESC');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.username', trans('admin.username'));
        $grid->column('user.name', trans('admin.name'));
        $grid->column('updated_at', trans('admin.updated_at'));
        $grid->disableActions();

        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append("<a class='btn btn-sm btn-default form-history-bac' href='".admin_url('auth/users')."'><i class='fa fa-arrow-left'></i>返回</a>");
        });
        $grid->filter(function (Grid\Filter $filter) {
            $userModel = config('admin.database.users_model');
            $filter->disableIdFilter();
            $filter->equal('user_id', trans('admin.name'))->select($userModel::all()->pluck('name', 'id'));
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });

        return $grid;
    }
}
