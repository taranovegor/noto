import { useEffect, useState } from 'react';
import { useParams, Navigate } from 'react-router-dom';
import { tokenStorage } from '../../shared/utils/tokenStorage';

const ENTITY_PATHS: Record<string, string> = {
  task: '/tasks',
  note: '/notes',
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

    const token = tokenStorage.getAccessToken();
    const headers: Record<string, string> = {};
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    fetch(`/api/refs/${id}`, { headers })
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
