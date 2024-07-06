<?php

namespace Forumkit\Install;

use Carbon\Carbon;
use Illuminate\Hashing\BcryptHasher;

class AdminUser
{
    private $username;
    private $password;
    private $email;

    public function __construct($username, $password, $email)
    {
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;

        $this->validate();
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getAttributes(): array
    {
        return [
            'username' => $this->username, // 用户输入的用户名
            'email' => $this->email, // 用户输入的电子邮件地址
            'password' => (new BcryptHasher)->make($this->password), // 使用Bcrypt哈希算法对密码进行加密
            'joined_at' => Carbon::now(), // 当前时间作为注册时间
            'is_email_confirmed' => 1, // 假设电子邮件已确认，标记为1
        ];
    }

    private function validate()
    {
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFailed('你必须输入一个有效的电子邮件地址。');
        }

        if (! $this->username || preg_match('/[^a-z0-9_-]/i', $this->username)) {
            throw new ValidationFailed('用户名只能包含字母、数字、下划线和破折号。');
        }
    }
}
