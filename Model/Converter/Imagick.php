<?php

namespace Ecomni\Webp\Model\Converter;

class Imagick extends AbstractConverter implements \Ecomni\Webp\Model\ConverterInterface
{
    /**
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function convert(string $filePath): array
    {
        $this->checkExtension();
        $url = $this->normalizeFilePath($filePath);
        if (!$this->file->fileExists($url)) {
            throw new \Exception(sprintf('File %s does not exist', $url));
        }
        if (!$this->isConvertibleImage($url)) {
            throw new \Exception(sprintf('File %s is not a convertible image', $url));
        }
        if ($this->imagick($url)) {
            $newPath = $this->replaceExtensions($filePath);
            return [
                'basename' => $this->file->getPathInfo($newPath)['basename'],
                'path' => $newPath,
                'full_path' => $this->normalizeFilePath($newPath)
            ];
        }
        throw new \Exception(sprintf('iMagick failed to convert %s', $url));
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function checkExtension(): void
    {
        if (!extension_loaded('imagick')) {
            throw new \Exception('Required iMagick extension is not available.');
        }

        if (!class_exists('\\Imagick')) {
            throw new \Exception('iMagick is installed, but not correctly. The class Imagick is not available');
        }

        $im = new \Imagick();
        if (!in_array('WEBP', $im->queryFormats('WEBP'))) {
            throw new \Exception('iMagick was compiled without WebP support.');
        }
    }

    protected function imagick(string $url): bool
    {
        $imagick = new \Imagick($url);
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality($this->config->getQuality());
        $imagick->setOption('webp:method', 6);
        $imagick->setOption('webp:lossless', false);
        $imagick->setOption('webp:low-memory', false);
        $imagick->setOption('webp:alpha-quality', 85);
        $imagick->setOption('webp:auto-filter', false);

        $profiles = $imagick->getImageProfiles('icc');
        $imagick->stripImage();
        if (!empty($profiles)) {
            $imagick->profileImage('icc', $profiles['icc']);
        }

        $imagick->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        return $imagick->writeImage($this->replaceExtensions($url));
    }
}
