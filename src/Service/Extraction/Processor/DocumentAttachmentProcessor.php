<?php

namespace App\Service\Extraction\Processor;

use App\Dto\Extraction\Plan\PlannedFragment;
use App\Dto\Extraction\Plan\SourceRef;
use App\Entity\Attachment;
use App\Enum\Extraction\FragmentType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Catch-all for every non-audio, non-image attachment (pdf, office documents,
 * spreadsheets, plain text, code). The bytes are never read here — the file is
 * handed to the multimodal model by URL at merge time, which decides how to
 * ingest it from the MIME type.
 *
 * Files the model cannot ingest are planned as a failed fragment so the rest of
 * the extraction still proceeds, rather than aborting the whole merge.
 */
#[AutoconfigureTag('app.extraction.attachment_processor', ['priority' => 10])]
final readonly class DocumentAttachmentProcessor implements AttachmentProcessor
{
    /**
     * Extensions OpenAI accepts as file inputs (rich documents, presentations,
     * spreadsheets, text and code). Anything `text/*` is also accepted regardless
     * of extension.
     *
     * @see https://developers.openai.com/api/docs/guides/file-inputs
     */
    private const array SUPPORTED_EXTENSIONS = [
        'pdf',
        // rich documents
        'doc', 'docx', 'dot', 'odt', 'rtf',
        // presentations
        'pot', 'ppa', 'pps', 'ppt', 'pptx', 'pwz', 'wiz',
        // spreadsheets
        'csv', 'tsv', 'iif', 'xla', 'xlb', 'xlc', 'xlm', 'xls', 'xlsx', 'xlt', 'xlw',
        // text and code
        'asm', 'bat', 'c', 'cc', 'conf', 'cpp', 'css', 'cxx', 'eml', 'h', 'htm',
        'html', 'js', 'json', 'md', 'pl', 'py', 'rst', 'sql', 'txt', 'xml',
    ];

    public function supports(Attachment $attachment): bool
    {
        $mimeType = $attachment->mimeType;

        return !str_starts_with($mimeType, 'audio/') && !str_starts_with($mimeType, 'image/');
    }

    public function plan(Attachment $attachment): iterable
    {
        if (!$this->isIngestible($attachment)) {
            yield PlannedFragment::failed(
                FragmentType::Document,
                sprintf('Unsupported file type for "%s".', $attachment->originFilename),
            );

            return;
        }

        yield PlannedFragment::of(
            FragmentType::Document,
            new SourceRef($attachment->path, $attachment->mimeType, $attachment->originFilename),
        );
    }

    private function isIngestible(Attachment $attachment): bool
    {
        if (str_starts_with($attachment->mimeType, 'text/')) {
            return true;
        }

        $extension = strtolower(pathinfo($attachment->originFilename, PATHINFO_EXTENSION));

        return in_array($extension, self::SUPPORTED_EXTENSIONS, true);
    }
}
