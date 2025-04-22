<?php

namespace Ecomni\Webp\Model\Converter;

class Cwebp extends AbstractConverter implements \Ecomni\Webp\Model\ConverterInterface
{
    /**
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function convert(string $filePath): array
    {
        $url = $this->normalizeFilePath($filePath);
        if (!$this->file->fileExists($url)) {
            throw new \Exception(sprintf('File %s does not exist', $url));
        }
        if (!$this->isConvertibleImage($url)) {
            throw new \Exception(sprintf('File %s is not a convertible image', $url));
        }
        if ($this->cWebP($url)) {
            $newPath = $this->replaceExtensions($filePath);
            return [
                'basename' => $this->file->getPathInfo($newPath)['basename'],
                'path' => $newPath,
                'full_path' => $this->normalizeFilePath($newPath)
            ];
        }
        throw new \Exception(sprintf('cWebp failed to convert %s', $url));
    }

    protected function cWebP(string $url): bool
    {
        $quality = $this->config->getQuality();
        $newUrl = $this->replaceExtensions($url);
        exec(sprintf('cwebp -q %d %s -o %s > dev/null 2>&1', (int)$quality, $url, $newUrl), $output, $resultCode);
        return $resultCode === 0;
    }
}
