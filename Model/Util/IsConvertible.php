<?php

namespace Ecomni\Webp\Model\Util;

use Magento\Framework\App\Filesystem\DirectoryList;

class IsConvertible
{
    public function __construct(
        protected LocalUrl $localUrl,
    ) {
    }

    public function isConvertibleImage(string $path): bool
    {
        return str_contains($path, '.png') || str_contains($path, '.jpg') || str_contains($path, '.jpeg');
    }

    public function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }
        if (!preg_match('/\.(jpg|jpeg|png)/i', $imageUrl)) {
            return false;
        }
        if (str_starts_with('data:', $imageUrl)) {
            return false;
        }
        if (!$this->localUrl->isLocal($imageUrl)) {
            return false;
        }
        if (str_contains($imageUrl, '/media/captcha/')) {
            return false;
        }
        return true;
    }
}
