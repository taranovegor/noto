<?php

namespace App\Event\Attachment;

use App\Entity\Attachment;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class AttachmentEvent extends Event implements ReferenceableEventInterface
{
    public const string Deleted = 'entity.attachment.deleted';

    public function __construct(
        public readonly Attachment $attachment,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Attachment;
    }
}
