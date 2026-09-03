import React from 'react';
import { Paperclip, Trash, Download } from 'lucide-react';
import { useLazyGetAttachmentDownloadUrlQuery } from '../../features/attachments';
import type { AttachmentResponseDto } from '../../features/attachments';
import { formatFileSize } from '../utils';
import styles from './AttachmentsList.module.css';

function AttachmentItem({
  attachment,
  onRemove,
  last,
}: {
  attachment: AttachmentResponseDto;
  onRemove?: () => void;
  last?: boolean;
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
    <div className={`${styles.attachmentItem} ${last ? styles.last : ''}`}>
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
        {onRemove && (
          <button
            type="button"
            className={styles.attachmentAction}
            onClick={onRemove}
            aria-label={`Remove ${attachment.originFilename}`}
          >
            <Trash size={14} strokeWidth={1.75} />
          </button>
        )}
      </div>
    </div>
  );
}

interface AttachmentsListProps {
  attachments: AttachmentResponseDto[];
  uploading?: boolean;
  uploadProgress?: number;
  uploadingFileName?: string | null;
  onRemove?: (attachmentId: string) => void;
}

export function AttachmentsList({
  attachments,
  uploading = false,
  uploadProgress,
  uploadingFileName,
  onRemove,
}: AttachmentsListProps) {
  if (!attachments.length && !uploading) return null;

  return (
    <div className={styles.attachments} role="list">
      {attachments.map((attachment, i) => (
        <AttachmentItem
          key={attachment.id}
          attachment={attachment}
          onRemove={onRemove ? () => onRemove(attachment.id) : undefined}
          last={i === attachments.length - 1 && !uploading}
        />
      ))}
      {uploading &&
        (uploadProgress !== undefined ? (
          <div className={`${styles.attachmentUploadingWrap} ${styles.last}`}>
            <div className={styles.attachmentUploadingRow}>
              <Paperclip size={14} strokeWidth={1.75} className={styles.attachmentIcon} />
              <div className={styles.attachmentMeta}>
                <span className={styles.attachmentName}>{uploadingFileName ?? 'Uploading…'}</span>
              </div>
              <span className={styles.uploadPercent}>{Math.round(uploadProgress * 100)}%</span>
            </div>
            <div className={styles.progressTrack}>
              <div
                className={styles.progressFill}
                style={{ width: `${Math.max(4, uploadProgress * 100)}%` }}
              />
            </div>
          </div>
        ) : (
          <div className={`${styles.attachmentUploading} ${styles.last}`}>
            <div
              className="skeleton"
              style={{ width: 14, height: 14, borderRadius: 2, flexShrink: 0 }}
            />
            <div className={styles.attachmentMeta}>
              <div className="skeleton skeleton-text" style={{ width: 140 }} />
              <div className="skeleton skeleton-text" style={{ width: 40 }} />
            </div>
          </div>
        ))}
    </div>
  );
}
