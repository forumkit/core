<?php

namespace Forumkit\Extension\Command;

use Forumkit\Extension\ExtensionManager;

class ToggleExtensionHandler
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @throws \Forumkit\User\Exception\PermissionDeniedException
     * @throws \Forumkit\Extension\Exception\MissingDependenciesException
     * @throws \Forumkit\Extension\Exception\DependentExtensionsException
     */
    public function handle(ToggleExtension $command)
    {
        $command->actor->assertAdmin();

        if ($command->enabled) {
            $this->extensions->enable($command->name);
        } else {
            $this->extensions->disable($command->name);
        }
    }
}
