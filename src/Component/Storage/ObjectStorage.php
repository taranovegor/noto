<?php

namespace App\Component\Storage;

use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\HeadObjectRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;

readonly class ObjectStorage
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
        private \DateInterval $ttl = new \DateInterval('PT5M'),
    ) {
    }

    public function uploadUrl(string $key, string $contentType, int $contentLength): string
    {
        return $this->s3Client->presign(
            new PutObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'ContentType' => $contentType,
                'ContentLength' => $contentLength,
            ]),
            new \DateTimeImmutable()->add($this->ttl),
        );
    }

    public function downloadUrl(string $key, string $filename): string
    {
        return $this->s3Client->presign(
            new GetObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'ResponseContentDisposition' => \sprintf('attachment; filename="%s"', $filename),
            ]),
            new \DateTimeImmutable()->add($this->ttl),
        );
    }

    public function delete(string $key): void
    {
        $this->s3Client->deleteObject(
            new DeleteObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]),
        );
    }

    public function exists(string $key): bool
    {
        try {
            $this->s3Client->headObject(
                new HeadObjectRequest([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                ]),
            );
        } catch (ClientException) {
            return false;
        }

        return true;
    }
}
