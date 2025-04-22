<?php

namespace Ecomni\Webp\Model;

interface ConverterInterface
{
    public function convert(string $filePath): array;
}
