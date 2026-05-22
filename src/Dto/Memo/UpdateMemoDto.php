<?php

namespace App\Dto\Memo;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateMemoDto
{
    public function __construct(
        #[Assert\Length(max: 65535)]
        public ?string $content = null,
    ) {
    }
}
