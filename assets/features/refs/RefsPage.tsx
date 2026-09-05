import { useEffect, useState } from 'react';
import { useParams, Navigate } from 'react-router-dom';

const ENTITY_PATHS: Record<string, string> = {
  task: '/tasks',
  memo: '/memos',
};

export function RefsPage() {
  const { id } = useParams<{ id: string }>();
  const [to, setTo] = useState<string | null>(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!id) {
      setFailed(true);
      return;
    }

    fetch(`/api/refs/${id}`)
      .then((r) => {
        if (!r.ok) throw new Error('not found');
        return r.json() as Promise<{ id: string; type: string }>;
      })
      .then((data) => {
        const path = ENTITY_PATHS[data.type];
        setTo(path ? `${path}/${data.id}` : null);
        setFailed(!path);
      })
      .catch(() => setFailed(true));
  }, [id]);

  if (failed) return <Navigate to="/" replace />;
  if (to) return <Navigate to={to} replace />;
  return null;
}
