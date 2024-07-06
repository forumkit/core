<?php

namespace Forumkit\User;

use Forumkit\Foundation\AbstractValidator;
use Forumkit\Foundation\ValidationException;
use Illuminate\Validation\Factory;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;

class AvatarValidator extends AbstractValidator
{
    /**
     * @var \Illuminate\Validation\Validator
     */
    protected $laravelValidator;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    public function __construct(Factory $validator, TranslatorInterface $translator, ImageManager $imageManager)
    {
        parent::__construct($validator, $translator);

        $this->imageManager = $imageManager;
    }

    /**
     * 如果模型无效，则引发异常。
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $this->laravelValidator = $this->makeValidator($attributes);

        $this->assertFileRequired($attributes['avatar']);
        $this->assertFileMimes($attributes['avatar']);
        $this->assertFileSize($attributes['avatar']);
    }

    protected function assertFileRequired(UploadedFileInterface $file)
    {
        $error = $file->getError();

        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $this->raise('file_too_large');
            }

            if ($error === UPLOAD_ERR_NO_FILE) {
                $this->raise('required');
            }

            $this->raise('file_upload_failed');
        }
    }

    protected function assertFileMimes(UploadedFileInterface $file)
    {
        $allowedTypes = $this->getAllowedTypes();

        // 阻止伪装成图像的 PHP 文件
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'phtml'];
        $fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if (in_array(trim(strtolower($fileExtension)), $phpExtensions)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        $guessedExtension = MimeTypes::getDefault()->getExtensions($file->getClientMediaType())[0] ?? null;

        if (! in_array($guessedExtension, $allowedTypes)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        try {
            $this->imageManager->make($file->getStream()->getMetadata('uri'));
        } catch (NotReadableException $_e) {
            $this->raise('image');
        }
    }

    protected function assertFileSize(UploadedFileInterface $file)
    {
        $maxSize = $this->getMaxSize();

        if ($file->getSize() / 1024 > $maxSize) {
            $this->raise('max.file', [':max' => $maxSize], 'max');
        }
    }

    protected function raise($error, array $parameters = [], $rule = null)
    {
        // 当我们切换到 intl ICU 消息格式时，翻译参数
        // 必须以 `{param}` 的格式提供。
        // 因此，我们无法使用翻译器来替换字符串参数。
        // 我们改为使用 Laravel 验证器来进行替换。
        $message = $this->laravelValidator->makeReplacements(
            $this->translator->trans("validation.$error"),
            'avatar',
            $rule ?? $error,
            array_values($parameters)
        );

        throw new ValidationException(['avatar' => $message]);
    }

    protected function getMaxSize()
    {
        return 2048;
    }

    protected function getAllowedTypes()
    {
        return ['jpeg', 'jpg', 'png', 'bmp', 'gif'];
    }
}
