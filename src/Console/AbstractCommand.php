<?php

namespace Forumkit\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        return $this->fire() ?: 0;
    }

    /**
     * 触发（执行）该命令。
     * 这是一个抽象方法，子类需要实现它。
     */
    abstract protected function fire();

    /**
     * 用户是否传递了给定的选项？
     *
     * @param string $name
     * @return bool
     */
    protected function hasOption($name)
    {
        return $this->input->hasOption($name);
    }

    /**
     * 向用户发送一个信息消息。
     *
     * @param string $message
     */
    protected function info($message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    /**
     * 向用户发送一个错误或警告消息。
     *
     * 如果可能的话，这个消息将通过STDERR发送。
     *
     * @param string $message
     */
    protected function error($message)
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->writeln("<error>$message</error>");
        } else {
            $this->output->writeln("<error>$message</error>");
        }
    }
}
