<?php
namespace Encore\LoginCheckSafe\Rules;
use Encore\LoginCheckSafe\Models\PasswordLogModel;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class AdminPasswordUsed 验证密码最近是否使用过
 * @package Encore\LoginCheckSafe\Rules
 * @author liujt 2019/8/13 13:37
 */
class AdminPasswordUsed implements Rule
{
    private $_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($_id)
    {
        $this->_id = $_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if($value==''){
            return true;
        }
        //判断密码是否使用过
        $list = PasswordLogModel::where('user_id',$this->_id)->limit(5)->orderByDesc('id')->get('password');
        $newpass = md5(config('app.key').$value);
        //var_dump($this->_id,$list);exit();
        foreach ($list as $v){
            if($v->password==$newpass){
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.password_used');
    }
}
