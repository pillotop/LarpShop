<?php

namespace App\Data\Images;

final readonly class CroppedImageProfile
{
    public function __construct(
        public int $width,
        public array $ratio,
        public array $responsiveWidths,
        public string $disk,
        public string $directory,
        public int $maxBytes,
        public int $quality,
    ) {}
}
