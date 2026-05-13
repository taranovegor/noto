import React from 'react';
import { Paperclip, Trash, Download } from 'lucide-react';
import { useLazyGetAttachmentDownloadUrlQuery } from '../../features/attachments';
import type { AttachmentResponseDto } from '../../features/attachments';
import styles from './AttachmentsList.module.css';

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function AttachmentItem({
  attachment,
  onRemove,
}: {
  attachment: AttachmentResponseDto;
  onRemove: () => void;
}) {
  const [fetchDownloadUrl, { isFetching }] = useLazyGetAttachmentDownloadUrlQuery();

  const handleDownload = async () => {
    const { downloadUrl } = await fetchDownloadUrl(attachment.id).unwrap();
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = downloadUrl;
    document.body.appendChild(iframe);
    setTimeout(() => document.body.removeChild(iframe), 2000);
  };

  return (
    <div className={styles.attachmentItem}>
      <Paperclip size={14} strokeWidth={1.75} className={styles.attachmentIcon} />
      <div className={styles.attachmentMeta}>
        <span className={styles.attachmentName}>{attachment.originFilename}</span>
        <span className={styles.attachmentSize}>{formatFileSize(attachment.size)}</span>
      </div>
      <div className={styles.attachmentActions}>
        <button
          type="button"
          className={styles.attachmentAction}
          onClick={handleDownload}
          disabled={isFetching}
          aria-label={`Download ${attachment.originFilename}`}
        >
          <Download size={14} strokeWidth={1.75} />
        </button>
        <button
          type="button"
          className={styles.attachmentAction}
          onClick={onRemove}
          aria-label={`Remove ${attachment.originFilename}`}
        >
          <Trash size={14} strokeWidth={1.75} />
        </button>
      </div>
    </div>
  );
}

interface AttachmentsListProps {
  attachments: AttachmentResponseDto[];
  uploading?: boolean;
  onRemove: (attachmentId: string) => void;
}

export function AttachmentsList({
  attachments,
  uploading = false,
  onRemove,
}: AttachmentsListProps) {
  if (!attachments.length && !uploading) return null;

  return (
    <div className={styles.attachments}>
      {attachments.map((attachment) => (
        <AttachmentItem
          key={attachment.id}
          attachment={attachment}
          onRemove={() => onRemove(attachment.id)}
        />
      ))}
      {uploading && (
        <div className={styles.attachmentUploading}>
          <div
            className="skeleton"
            style={{ width: 14, height: 14, borderRadius: 2, flexShrink: 0 }}
          />
          <div className={styles.attachmentMeta}>
            <div className="skeleton skeleton-text" style={{ width: 140 }} />
            <div className="skeleton skeleton-text" style={{ width: 60, marginTop: 2 }} />
          </div>
        </div>
      )}
    </div>
  );
}
