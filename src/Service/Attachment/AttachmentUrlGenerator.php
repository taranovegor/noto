<?php

namespace App\Service\Attachment;

use App\Entity\Attachment;
use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\HeadObjectRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;

readonly class AttachmentUrlGenerator
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
        private \DateInterval $ttl = new \DateInterval('PT5M'),
    ) {
    }

    public function generateUploadUrl(Attachment $attachment): string
    {
        return $this->s3Client->presign(
            new PutObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $attachment->path,
                'ContentType' => $attachment->mimeType,
                'ContentLength' => $attachment->size,
            ]),
            new \DateTimeImmutable()->add($this->ttl),
        );
    }

    public function generateDownloadUrl(Attachment $attachment): string
    {
        return $this->s3Client->presign(
            new GetObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $attachment->path,
                'ResponseContentDisposition' => \sprintf('attachment; filename="%s"', $attachment->originFilename),
            ]),
            new \DateTimeImmutable()->add($this->ttl),
        );
    }

    public function objectExists(Attachment $attachment): bool
    {
        try {
            $this->s3Client->headObject(
                new HeadObjectRequest([
                    'Bucket' => $this->bucket,
                    'Key' => $attachment->path,
                ]),
            );
        } catch (ClientException) {
            return false;
        }

        return true;
    }
}
