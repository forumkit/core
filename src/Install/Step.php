<?php

namespace Forumkit\Install;

interface Step
{
    /**
     * 一行状态消息，总结本步骤正在发生的事情。
     *
     * @return string
     */
    public function getMessage();

    /**
     * 执行构成此步骤的工作。
     *
     * 当发生某些错误导致整个安装需要回滚时，该方法应抛出一个`StepFailed`异常。
     *
     * @return void
     * @throws StepFailed
     */
    public function run();
}
