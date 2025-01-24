<?php

namespace Ecomni\Webp\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

class WebpConverter
{
    public function __construct(
        protected \Magento\Framework\Filesystem\Io\File $file,
        protected \Ecomni\Webp\Model\Config\Config $config,
    ) {
    }

    public function convert(string $filePath): ?array
    {
        $url = $this->normalizeFilePath($filePath);
        if (!$this->file->fileExists($url)) {
            return null;
        }
        if ($this->cWebP($url)) {
            $newPath = $this->replaceExtensions($filePath);
            return [
                'basename' => $this->file->getPathInfo($newPath)['basename'],
                'path' => $newPath,
                'full_path' => $this->normalizeFilePath($newPath)
            ];
        }
        return null;
    }

    protected function replaceExtensions(string $path): string
    {
        return str_replace(['.jpg', '.jpeg', '.png'], '.webp', $path);
    }

    protected function cWebP(string $url): bool
    {
        $quality = $this->config->getQuality();
        $newUrl = $this->replaceExtensions($url);
        exec(sprintf('cwebp -q %d %s -o %s', (int)$quality, $url, $newUrl), $output, $resultCode);
        return $resultCode === 0;
    }

    /**
     * Files can have (parts of) /pub/media in their name.
     * Here we make sure we always work with the `pub/media/{rest_of_path}` format.
     *
     * @param string $filePath
     * @return string
     */
    protected function normalizeFilePath(string $filePath): string
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
}
