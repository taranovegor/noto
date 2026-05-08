import { useCallback, useState } from 'react';
import { parseError } from '../../../shared/utils';
import { useAppDispatch } from '../../../shared/store/hooks';
import { useGetBatchAttachmentDownloadUrlMutation } from '../../attachments';
import { useRealtimeEvents } from '../../../shared/websocket';
import { StashCard } from './StashCard';
import { StashesListSkeleton } from './StashesListSkeleton';
import {
  stashesApi,
  useGetStashesQuery,
  useUpdateStashMutation,
  useDeleteStashMutation,
} from '../store/api';
import type { StashResponseDto } from '../types';
import styles from './StashesList.module.css';

const STASHES_QUERY_PARAMS = { limit: 200 };

function downloadUrls(results: { downloadUrl: string }[]) {
  for (const { downloadUrl } of results) {
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = downloadUrl;
    document.body.appendChild(iframe);
    setTimeout(() => document.body.removeChild(iframe), 2000);
  }
}

export function StashesList() {
  const { data, isLoading, error } = useGetStashesQuery(STASHES_QUERY_PARAMS);

  const dispatch = useAppDispatch();
  const [updateStash] = useUpdateStashMutation();
  const [deleteStash] = useDeleteStashMutation();
  const [fetchBatchDownloadUrl] = useGetBatchAttachmentDownloadUrlMutation();
  const [deletingId, setDeletingId] = useState<string | null>(null);

  useRealtimeEvents('stashes', {
    onCreated: useCallback(() => {
      dispatch(stashesApi.util.invalidateTags(['Stashes']));
    }, [dispatch]),
    onUpdated: useCallback(() => {
      dispatch(stashesApi.util.invalidateTags(['Stashes']));
    }, [dispatch]),
    onDeleted: useCallback(
      (data: Record<string, unknown>) => {
        const id = data.id as string;
        if (!id) return;

        dispatch(
          stashesApi.util.updateQueryData('getStashes', STASHES_QUERY_PARAMS, (draft) => {
            draft.data = draft.data.filter((s) => s.id !== id);
            draft.pagination.total = Math.max(0, draft.pagination.total - 1);
          }),
        );
      },
      [dispatch],
    ),
  });

  const all = data?.data ?? [];
  const now = Date.now();
  const activeStashes = all.filter((s) => !s.expiresAt || Date.parse(s.expiresAt) >= now);
  const expiredStashes = all.filter((s) => s.expiresAt && Date.parse(s.expiresAt) < now);

  const handleDownload = useCallback(
    async (attachmentIds: string[]) => {
      try {
        const results = await fetchBatchDownloadUrl({ ids: attachmentIds }).unwrap();
        downloadUrls(results);
      } catch {
        // Download URL fetch failed — silent, user retries implicitly
      }
    },
    [fetchBatchDownloadUrl],
  );

  const handleCopy = useCallback((stash: StashResponseDto) => {
    if (!stash.content) return;

    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(stash.content).catch(() => {
        // Fallback via deprecated execCommand — only used when Clipboard API fails
        const el = document.createElement('textarea');
        el.value = stash.content!;
        el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
      });
    } else {
      const el = document.createElement('textarea');
      el.value = stash.content;
      el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);
    }
  }, []);

  const handlePin = useCallback(
    (stash: StashResponseDto) => {
      updateStash({ id: stash.id, body: { pinned: !stash.pinned } });
    },
    [updateStash],
  );

  const handleDelete = useCallback(
    async (stash: StashResponseDto) => {
      setDeletingId(stash.id);
      try {
        await deleteStash(stash.id).unwrap();
      } catch {
        // Delete failed — button returns to normal, user can retry
      } finally {
        setDeletingId(null);
      }
    },
    [deleteStash],
  );

  const errorMessage = error ? parseError(error).message : null;

  if (isLoading) {
    return <StashesListSkeleton />;
  }

  return (
    <div className={styles.container}>
      {errorMessage && (
        <div className="error-message" role="alert">
          {errorMessage}
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
                  onDelete={handleDelete}
                  isDeleting={deletingId === stash.id}
                  isExpired={false}
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
      {expiredStashes.length > 0 && (
        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Expired</h2>
          <div className={styles.grid} role="list">
            {expiredStashes.map((stash) => (
              <div key={stash.id} role="listitem">
                <StashCard
                  stash={stash}
                  onDownload={handleDownload}
                  onCopy={handleCopy}
                  onPin={handlePin}
                  onDelete={handleDelete}
                  isDeleting={deletingId === stash.id}
                  isExpired={true}
                />
              </div>
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
