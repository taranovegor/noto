import { useCallback } from 'react';
import { parseError } from '../../../shared/utils';
import { useLazyGetAttachmentDownloadUrlQuery } from '../../attachments';
import { StashCard } from './StashCard';
import { StashesListSkeleton } from './StashesListSkeleton';
import { useGetStashesQuery, useUpdateStashMutation } from '../store/api';
import type { StashResponseDto } from '../types';
import styles from './StashesList.module.css';

function copyViaExecCommand(text: string) {
  const el = document.createElement('textarea');
  el.value = text;
  el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
  document.body.appendChild(el);
  el.focus();
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
}

export function StashesList() {
  const {
    data: activeData,
    isLoading: activeLoading,
    error: activeError,
  } = useGetStashesQuery({
    filterActive: true,
    limit: 100,
  });

  const {
    data: expiredData,
    isLoading: expiredLoading,
    error: expiredError,
  } = useGetStashesQuery({
    filterActive: false,
    limit: 100,
  });

  const [updateStash] = useUpdateStashMutation();
  const [fetchDownloadUrl] = useLazyGetAttachmentDownloadUrlQuery();

  const activeStashes = activeData?.data ?? [];

  const handleDownload = useCallback(
    async (attachmentId: string) => {
      try {
        const { downloadUrl } = await fetchDownloadUrl(attachmentId).unwrap();
        window.location.href = downloadUrl;
      } catch (err) {
        console.error('Failed to get download URL:', err);
      }
    },
    [fetchDownloadUrl],
  );

  const handleCopy = useCallback((stash: StashResponseDto) => {
    if (!stash.content) return;

    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(stash.content).catch(() => {
        copyViaExecCommand(stash.content!);
      });
    } else {
      copyViaExecCommand(stash.content);
    }
  }, []);

  const handlePin = useCallback(
    (stash: StashResponseDto) => {
      void updateStash({
        id: stash.id,
        body: { pinned: !stash.pinned },
      });
    },
    [updateStash],
  );

  const activeError_ = activeError ? parseError(activeError).message : null;
  const expiredError_ = expiredError ? parseError(expiredError).message : null;

  if (activeLoading || expiredLoading) {
    return <StashesListSkeleton />;
  }

  return (
    <div className={styles.container}>
      {activeError_ && (
        <div className="error-message" role="alert">
          {activeError_}
        </div>
      )}
      {expiredError_ && (
        <div className="error-message" role="alert">
          {expiredError_}
        </div>
      )}

      {/* Active Section */}
      <section className={styles.section}>
        <h2 className={styles.sectionTitle}>Active</h2>
        {activeStashes.length > 0 ? (
          <div className={styles.grid} role="list">
            {activeStashes.map((stash) => (
              <div key={stash.id} role="listitem">
                <StashCard
                  stash={stash}
                  onDownload={handleDownload}
                  onCopy={handleCopy}
                  onPin={handlePin}
                />
              </div>
            ))}
          </div>
        ) : (
          <div className={styles.emptyState}>
            <p>No active stashes yet.</p>
          </div>
        )}
      </section>

      {/* Expired Section */}
      {(expiredData?.data ?? []).length > 0 && (
        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Expired</h2>
          <div className={styles.grid} role="list">
            {(expiredData?.data ?? []).map((stash) => (
              <div key={stash.id} role="listitem">
                <StashCard
                  stash={stash}
                  onDownload={handleDownload}
                  onCopy={handleCopy}
                  onPin={handlePin}
                />
              </div>
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
