<?php

namespace Encore\LoginCheckSafe\Http\Controllers;

use Encore\Admin\Auth\Database\OperationLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class LogController extends Controller
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
            ->header(trans('admin.operation_log'))
            ->description('')
            ->body($this->grid());
    }
    /**
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OperationLog());

        $grid->model()->orderBy('id', 'DESC');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.username', trans('admin.username'));
        $grid->column('user.name', trans('admin.name'));
        $grid->column('method',trans('admin.http.method'))->display(function ($method) {
            $color = Arr::get(OperationLog::$methodColors, $method, 'grey');

            return "<span class=\"badge bg-$color\">$method</span>";
        });
        $grid->column('path',trans('admin.http.path'))->label('info');
        $grid->column('ip')->label('primary');
        $grid->column('input',trans('admins.input'))->display(function ($input) {
            $input = json_decode($input, true);
            $input = Arr::except($input, ['_pjax', '_token', '_method', '_previous_']);
            if (empty($input)) {
                return '<code>{}</code>';
            }

            return '<pre>'.json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
        });

        $grid->column('created_at', trans('admin.created_at'));

        $grid->disableActions();

        $grid->disableCreateButton();

        $grid->filter(function (Grid\Filter $filter) {
            $userModel = config('admin.database.users_model');
            //$filter->disableIdFilter();
            $filter->equal('user_id', trans('admin.name'))->select($userModel::all()->pluck('name', 'id'));
            $filter->equal('method',trans('admin.http.method'))->select(array_combine(OperationLog::$methods, OperationLog::$methods));
            $filter->like('path',trans('admin.http.path'));
            $filter->equal('ip');
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });

        return $grid;
    }
}
