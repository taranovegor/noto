import { useState } from 'react';
import { FileText, Files, Pin, Download, Copy } from 'lucide-react';
import { getMimeTypeIcon, type StashResponseDto } from '../index';
import { formatRelative } from '../../../shared/utils';
import styles from './StashCard.module.css';

interface StashCardProps {
  stash: StashResponseDto;
  onDownload?: (attachmentId: string) => void;
  onCopy?: (stash: StashResponseDto) => void;
  onPin?: (stash: StashResponseDto) => void;
}

export function StashCard({ stash, onDownload, onCopy, onPin }: StashCardProps) {
  const [isExpanded, setIsExpanded] = useState(false);

  const isFileStash = stash.type === 'file';
  const hasAttachments = stash.attachments && stash.attachments.length > 0;
  const firstAttachment = hasAttachments ? stash.attachments![0] : null;
  const attachmentCount = stash.attachments?.length ?? 0;
  const isMultiFile = isFileStash && attachmentCount > 1;

  let IconComponent = FileText;
  let title = '';

  if (isFileStash && firstAttachment) {
    IconComponent = isMultiFile ? Files : getMimeTypeIcon(firstAttachment.mimeType);
    title = isMultiFile
      ? stash.attachments!.map((a) => a.originFilename).join(' · ')
      : firstAttachment.originFilename;
  } else if (!isFileStash && stash.content) {
    title = stash.content.slice(0, 80).replace(/\n/g, ' ');
    if (stash.content.length > 80) title += '...';
  }

  return (
    <div className={styles.card}>
      <div
        className={`${styles.mainRow} ${isMultiFile ? styles.mainRowClickable : ''}`}
        onClick={isMultiFile ? () => setIsExpanded((v) => !v) : undefined}
        role={isMultiFile ? 'button' : undefined}
        tabIndex={isMultiFile ? 0 : undefined}
        onKeyDown={
          isMultiFile
            ? (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  setIsExpanded((v) => !v);
                }
              }
            : undefined
        }
        aria-expanded={isMultiFile ? isExpanded : undefined}
      >
        <div className={styles.icon}>
          <IconComponent size={18} />
        </div>

        <div className={styles.titleBlock}>
          <span className={styles.title}>{title || 'n/a'}</span>
          {isMultiFile && <span className={styles.badge}>{attachmentCount}</span>}
        </div>

        <div className={styles.meta}>
          <span className={styles.date}>{formatRelative(stash.createdAt)}</span>
          {stash.expiresAt && (
            <span className={styles.expires}>
              {new Date(stash.expiresAt) < new Date() ? 'expired' : 'expires'}{' '}
              {formatRelative(stash.expiresAt)}
            </span>
          )}
        </div>

        <div className={styles.actions}>
          {isFileStash && hasAttachments ? (
            <button
              className={`btn btn-ghost ${styles.actionBtn}`}
              onClick={(e) => {
                e.stopPropagation();
                onDownload?.(stash.attachments![0].id);
              }}
              title="Download all"
              aria-label="Download all"
            >
              <Download size={15} />
            </button>
          ) : (
            <button
              className={`btn btn-ghost ${styles.actionBtn}`}
              onClick={(e) => {
                e.stopPropagation();
                onCopy?.(stash);
              }}
              title="Copy"
              aria-label="Copy"
            >
              <Copy size={15} />
            </button>
          )}
          <button
            className={`${styles.pinButton} ${stash.pinned ? styles.pinned : ''}`}
            onClick={(e) => {
              e.stopPropagation();
              onPin?.(stash);
            }}
            title={stash.pinned ? 'Unpin' : 'Pin'}
            aria-label={stash.pinned ? 'Unpin' : 'Pin'}
          >
            <Pin size={15} />
          </button>
        </div>
      </div>

      {isExpanded && (
        <div className={styles.fileList}>
          {stash.attachments!.map((attachment) => {
            const FileIcon = getMimeTypeIcon(attachment.mimeType);
            return (
              <div key={attachment.id} className={styles.fileRow}>
                <div className={styles.fileRowIcon}>
                  <FileIcon size={14} />
                </div>
                <span className={styles.fileRowName}>{attachment.originFilename}</span>
                <button
                  className={`btn btn-ghost ${styles.fileRowAction}`}
                  onClick={() => onDownload?.(attachment.id)}
                  title={`Download ${attachment.originFilename}`}
                  aria-label={`Download ${attachment.originFilename}`}
                >
                  <Download size={13} />
                </button>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
