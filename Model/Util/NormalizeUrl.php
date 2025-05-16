<?php

namespace Ecomni\Webp\Model\Util;

use Magento\Catalog\Model\Category\FileInfo;
use Magento\Framework\App\Filesystem\DirectoryList;

class NormalizeUrl
{
    /**
     * Files can have (parts of) /pub/media in their name.
     * Here we make sure we always work with the `pub/media/{rest_of_path}` format.
     *
     * @param string $filePath
     * @return string
     */
    public function normalizeFilePath(string $filePath): string
    {
        $filePath = str_replace(
            [
                DirectoryList::PUB,
                DirectoryList::MEDIA,
            ],
            '',
            $filePath
        );
        $filePath = ltrim($filePath, '/');
        return sprintf('%s/%s/%s', DirectoryList::PUB, DirectoryList::MEDIA, $filePath);
    }

    /**
     * Convert {{media url=xxx}} into media/xxx.
     *
     * @param string $filePath
     * @return string
     */
    public function normalizeInlineFilePath(string $filePath): string
    {
        $filePath = str_replace(
            [
                DirectoryList::PUB,
                DirectoryList::MEDIA,
                '{{',
                'url=',
                '}}'
            ],
            '',
            $filePath
        );
        $filePath = ltrim($filePath);
        return sprintf('/%s/%s', DirectoryList::MEDIA, $filePath);
    }

    /**
     * Files can be stored as `{image}` or as `catalog/category/{image}` in the database.
     * Here we make sure we always work with the `catalog/category/{image}` format.
     *
     * @param string $filePath
     * @return string
     */
    public function normalizeCatalogFilePath(string $filePath): string
    {
        $filePath = str_replace(
            [
                DirectoryList::PUB,
                DirectoryList::MEDIA,
                FileInfo::ENTITY_MEDIA_PATH,
            ],
            '',
            $filePath
        );
        $filePath = ltrim($filePath, '/');
        return sprintf('%s/%s', FileInfo::ENTITY_MEDIA_PATH, $filePath);
    }

    /**
     * Convert media/xxx into {{media url=xxx}}.
     *
     * @param string $filePath
     * @return string
     */
    public function encodeFilePath(string $filePath): string
    {
        $filePath = ltrim(str_replace('media', '', $filePath), '/');
        return sprintf('{{media url=%s}}', $filePath);
    }
}
