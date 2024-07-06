<?php

namespace Forumkit\Mail;

use Illuminate\Mail\Transport\LogTransport;
use Swift_Mime_SimpleMessage;

class ForumkitLogTransport extends LogTransport
{
    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        // 覆盖以使用信息，因此日志驱动程序在非调试模式下工作。
        $this->logger->info($this->getMimeEntityString($message));

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }
}
