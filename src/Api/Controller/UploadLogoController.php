<?php

namespace Forumkit\Api\Controller;

use Forumkit\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;

class UploadLogoController extends UploadImageController
{
    protected $filePathSettingKey = 'logo_path';

    protected $filenamePrefix = 'logo';

    /**
     * @var ImageManager
     */
    protected $imageManager;

    public function __construct(SettingsRepositoryInterface $settings, Factory $filesystemFactory, ImageManager $imageManager)
    {
        parent::__construct($settings, $filesystemFactory);

        $this->imageManager = $imageManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function makeImage(UploadedFileInterface $file): Image
    {
        $encodedImage = $this->imageManager->make($file->getStream()->getMetadata('uri'))->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');

        return $encodedImage;
    }
}
