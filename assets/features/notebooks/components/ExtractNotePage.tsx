import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Sparkles, Paperclip } from 'lucide-react';
import { useCreateExtractionMutation, useGetExtractionQuery } from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';
import type { AttachmentResponseDto } from '../../attachments';
import { FormShell, AttachmentsList } from '../../../shared/components';
import { DragDropZone } from '../../../shared/components/DragDropZone';
import { useActionBar } from '../../../layout/ActionBarContext';
import { parseError } from '../../../shared/utils';
import styles from './ExtractNotePage.module.css';

export function ExtractNotePage() {
  const { notebookId } = useParams<{ notebookId: string }>();

  if (!notebookId) {
    return (
      <FormShell
        backTo="/notebooks"
        error="Notebook not found"
        showSaveBar={false}
        onSubmit={() => {}}
      >
        <div />
      </FormShell>
    );
  }

  return <ExtractNotePageInner notebookId={notebookId} />;
}

type PageStatus =
  | { kind: 'idle' }
  | { kind: 'submitting' }
  | { kind: 'pending'; extractionId: string }
  | { kind: 'processing'; extractionId: string }
  | { kind: 'done'; extractionId: string }
  | { kind: 'failed'; extractionId: string; errorMessage: string | null };

function ExtractNotePageInner({ notebookId }: { notebookId: string }) {
  const navigate = useNavigate();

  const [createExtraction] = useCreateExtractionMutation();
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmAttachmentUpload] = useConfirmAttachmentUploadMutation();

  const [prompt, setPrompt] = useState('');
  const [pendingAttachments, setPendingAttachments] = useState<AttachmentResponseDto[]>([]);
  const [attachUploading, setAttachUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [pageStatus, setPageStatus] = useState<PageStatus>({ kind: 'idle' });

  const fileInputRef = useRef<HTMLInputElement>(null);

  const extractionId =
    pageStatus.kind !== 'idle' && pageStatus.kind !== 'submitting' ? pageStatus.extractionId : null;

  const shouldPoll = pageStatus.kind === 'pending' || pageStatus.kind === 'processing';

  const { data: extraction } = useGetExtractionQuery(extractionId!, {
    skip: !extractionId,
    pollingInterval: shouldPoll ? 2000 : 0,
  });

  useEffect(() => {
    if (extraction) {
      const id = extraction.id;
      if (extraction.status === 'done') {
        setPageStatus({ kind: 'done', extractionId: id });
      } else if (extraction.status === 'failed') {
        setPageStatus({
          kind: 'failed',
          extractionId: id,
          errorMessage: extraction.errorMessage,
        });
      } else if (extraction.status === 'processing') {
        setPageStatus({ kind: 'processing', extractionId: id });
      }
    }
  }, [extraction]);

  const handleFilesUpload = useCallback(
    async (files: File[]) => {
      if (files.length === 0) return;

      setAttachUploading(true);
      setError(null);

      try {
        const uploaded = await Promise.all(
          files.map(async (file) => {
            const { uploadUrl, id } = await createAttachment({
              originFilename: file.name,
              mimeType: file.type || 'application/octet-stream',
              size: file.size,
            }).unwrap();

            const putRes = await fetch(uploadUrl, {
              method: 'PUT',
              body: file,
              headers: { 'Content-Type': file.type || 'application/octet-stream' },
            });

            if (!putRes.ok) {
              throw new Error('Failed to upload file to storage');
            }

            return confirmAttachmentUpload(id).unwrap();
          }),
        );
        setPendingAttachments((prev) => [...prev, ...uploaded]);
      } catch (err: unknown) {
        setError(parseError(err).message || 'Failed to upload attachment');
      } finally {
        setAttachUploading(false);
      }
    },
    [confirmAttachmentUpload, createAttachment],
  );

  const handleFileInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const files = Array.from(e.target.files ?? []);
      e.target.value = '';
      void handleFilesUpload(files);
    },
    [handleFilesUpload],
  );

  const handleDetach = useCallback((attachmentId: string) => {
    setPendingAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
  }, []);

  const handleStartExtraction = useCallback(async () => {
    if (pendingAttachments.length === 0) {
      setError('Please attach at least one file');
      return;
    }

    setError(null);
    setPageStatus({ kind: 'submitting' });

    try {
      const result = await createExtraction({
        attachments: pendingAttachments.map((a) => a.id),
        targetType: 'note',
        targetParent: notebookId,
        prompt: prompt.trim() || null,
      }).unwrap();

      setPageStatus({
        kind: result.status === 'processing' ? 'processing' : 'pending',
        extractionId: result.id,
      });
    } catch (err: unknown) {
      setError(parseError(err).message);
      setPageStatus({ kind: 'idle' });
    }
  }, [createExtraction, notebookId, pendingAttachments, prompt]);

  const canAct = pageStatus.kind === 'idle';

  const actionLabel = (() => {
    switch (pageStatus.kind) {
      case 'idle':
        return 'Start extraction';
      case 'submitting':
        return 'Submitting...';
      case 'pending':
        return 'Pending...';
      case 'processing':
        return 'Processing...';
      case 'done':
        return 'Done';
      case 'failed':
        return 'Failed';
    }
  })();

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => navigate(`/notebooks/${notebookId}`),
    },
    buttons: [
      {
        icon: Sparkles,
        label: actionLabel,
        primary: true,
        disabled: !canAct || pendingAttachments.length === 0,
        onPress: handleStartExtraction,
      },
      {
        icon: Paperclip,
        label: 'Attach file',
        disabled: !canAct || attachUploading,
        onPress: () => fileInputRef.current?.click(),
      },
    ],
  });

  return (
    <FormShell
      backTo={`/notebooks/${notebookId}`}
      error={error}
      showSaveBar={false}
      onSubmit={(e) => {
        e.preventDefault();
        handleStartExtraction();
      }}
      extraActions={
        <button
          type="submit"
          className="btn btn-primary"
          disabled={!canAct || pendingAttachments.length === 0}
        >
          {actionLabel}
        </button>
      }
    >
      <h1>Extract note</h1>

      <div className={styles.section}>
        <input
          ref={fileInputRef}
          type="file"
          multiple
          style={{ display: 'none' }}
          onChange={handleFileInputChange}
          disabled={!canAct}
        />
        <DragDropZone
          onDrop={handleFilesUpload}
          disabled={!canAct}
          uploading={attachUploading}
          hideOnMobile={false}
          hint="Drop files, or click to browse"
        />
        <AttachmentsList
          attachments={pendingAttachments}
          uploading={attachUploading}
          onRemove={canAct ? handleDetach : undefined}
        />
      </div>

      <div className={styles.section}>
        <label className={styles.fieldLabel}>
          Instructions
          <textarea
            className={styles.promptInput}
            value={prompt}
            onChange={(e) => setPrompt(e.target.value)}
            placeholder="What should the AI extract from these files?"
            disabled={!canAct}
          />
        </label>
      </div>

      {pageStatus.kind !== 'idle' && (
        <div className={styles.statusSection}>
          {(pageStatus.kind === 'submitting' || pageStatus.kind === 'pending') && (
            <div className={styles.statusPending}>
              <div className={`skeleton ${styles.statusIndicator}`} />
              <span>Starting extraction…</span>
            </div>
          )}
          {pageStatus.kind === 'processing' && (
            <div className={styles.statusProcessing}>
              <div className={`skeleton ${styles.statusIndicator}`} />
              <div>
                <p className={styles.statusTitle}>Extracting…</p>
                <p className={styles.statusHint}>
                  Analyzing files and extracting content. This usually takes a few seconds.
                </p>
              </div>
            </div>
          )}
          {pageStatus.kind === 'done' && <div className={styles.statusSuccess}>Note created</div>}
          {pageStatus.kind === 'failed' && (
            <div className={styles.statusFailed}>
              <p className={styles.statusFailedTitle}>Extraction failed</p>
              {pageStatus.errorMessage && (
                <p className={styles.statusFailedDetail}>{pageStatus.errorMessage}</p>
              )}
              {!pageStatus.errorMessage && (
                <p className={styles.statusFailedDetail}>Please check your files and try again.</p>
              )}
              <button
                type="button"
                className="btn-ghost"
                style={{ marginTop: 'var(--space-md)' }}
                onClick={() => {
                  setError(null);
                  setPageStatus({ kind: 'idle' });
                }}
              >
                Try again
              </button>
            </div>
          )}
        </div>
      )}
    </FormShell>
  );
}
