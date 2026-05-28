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

    /**
     * Streams the stored item into a local temp file, preserving the filename
     * extension.
     */
    public function download(string $key, string $filename): \SplFileInfo
    {
        $result = $this->s3Client->getObject(
            new GetObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]),
        );

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $suffix = '' !== $extension ? '.'.$extension : '';

        $tempFile = tempnam(sys_get_temp_dir(), 'storage_dl_');

        if ('' !== $suffix) {
            $renamed = $tempFile.$suffix;
            rename($tempFile, $renamed);
            $tempFile = $renamed;
        }

        $target = fopen($tempFile, 'w');

        try {
            stream_copy_to_stream($result->getBody()->getContentAsResource(), $target);
        } finally {
            fclose($target);
        }

        return new \SplFileInfo($tempFile);
    }

    /**
     * Streams a local file into storage under the given key.
     */
    public function upload(string $key, \SplFileInfo $file): void
    {
        $contentType = mime_content_type($file->getPathname());
        $body = fopen($file->getPathname(), 'r');

        try {
            $this->s3Client->putObject(
                new PutObjectRequest([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'ContentType' => $contentType,
                    'ContentLength' => (int) $file->getSize(),
                    'Body' => $body,
                ]),
            )->resolve();
        } finally {
            if (is_resource($body)) {
                fclose($body);
            }
        }
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
