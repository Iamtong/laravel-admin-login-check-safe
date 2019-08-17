<?php
namespace Encore\LoginCheckSafe\Rules;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class AdminPassword 验证密码组成必须有 数字 大写字母 小写字母 特殊字符 三种及上元素组成
 * @package Encore\LoginCheckSafe\Rules
 * @author liujt 2019/8/13 13:37
 */
class AdminPassword implements Rule
{
    private $_strong_num=3;
    private $_message = '';
    public function setMessage($message){
        $this->_message = $message;
    }
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_strong_num = config('admin.extensions.login-check-safe.password-strong');
        if($this->_strong_num>4){
            $this->_strong_num = 4;
        }elseif($this->_strong_num<=0){
            $this->_strong_num = 2;//没有配置那么就默认两种
        }
        //var_dump($this->_strong_num);exit();
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
        if ($value==''){
            return true;
        }
        $passLength = '10,100';
        if(config('admin.extensions.login-check-safe.password-length')){
            $passLength = config('admin.extensions.login-check-safe.password-length');
        }
        list($start,$end) = explode(',',$passLength);
        if ($start>0&&strlen($value)<$start){
            $this->setMessage(trans('validation.password_length_min',['num'=>$start]));
            return false;
        }
        if ($end>$start&&strlen($value)>$end){
            $this->setMessage(trans('validation.password_length_max',['num'=>$end]));
            return false;
        }
        if ($this->getPassStrength($value)<$this->_strong_num){
            $this->setMessage(trans('validation.adminpassword',['num'=>$this->_strong_num]));
            return false;
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
        return $this->_message;
    }
    /**
     * 计算密码强度, 与客户端一致
     * @param type $password
     * @return int
     */
    public function getPassStrength($password) {
        $partArr = array('/[0-9]/', '/[a-z]/', '/[A-Z]/', '/[\W_]/');
        $score = 0;
        foreach ($partArr as $part) {
            if (preg_match($part, $password))
                $score += 1; //某类型存在加分
        }
        return $score;
    }
}
