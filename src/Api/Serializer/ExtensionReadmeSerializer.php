<?php

namespace Forumkit\Api\Serializer;

class ExtensionReadmeSerializer extends AbstractSerializer
{
    /**
     * @param \Forumkit\Extension\Extension $extension
     * @return array
     */
    protected function getDefaultAttributes($extension)
    {
        $attributes = [
            'content' => $extension->getReadme()
        ];

        return $attributes;
    }

    public function getId($extension)
    {
        return $extension->getId();
    }

    public function getType($extension)
    {
        return 'extension-readmes';
    }
}
