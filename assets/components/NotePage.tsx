import React, { useState, useEffect } from 'react';
import { NoteResponseDto, UpdateNoteDto } from '../types/api';
import { api } from '../api';
import { formatDateTime } from '../utils/date';
import { MarkdownEditor } from './MarkdownEditor';

interface NotePageProps {
  noteId?: string;
  onBack: () => void;
  onCreated?: (noteId: string) => void;
}

export function NotePage({ noteId, onBack, onCreated }: NotePageProps) {
  const [note, setNote] = useState<NoteResponseDto | null>(null);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(noteId ? true : false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isDirty, setIsDirty] = useState(false);

  useEffect(() => {
    if (!noteId) {
      return;
    }

    api.notes.get(noteId)
      .then((data) => {
        setNote(data);
        setTitle(data.title);
        setContent(data.content);
      })
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'Failed to load note');
      })
      .finally(() => setLoading(false));
  }, [noteId]);

  const handleTitleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setTitle(e.target.value);
    setIsDirty(true);
  };

  const handleContentChange = (newContent: string) => {
    setContent(newContent);
    setIsDirty(true);
  };

  const handleSave = async () => {
    if (!title.trim() || !content.trim()) {
      setError('Title and content are required');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      if (noteId) {
        const dto: UpdateNoteDto = {
          title: title.trim(),
          content: content.trim(),
        };
        const updated = await api.notes.update(noteId, dto);
        setNote(updated);
      } else {
        const created = await api.notes.create({
          title: title.trim(),
          content: content.trim(),
        });
        setNote(created);
        setIsDirty(false);
        onCreated?.(created.id);
      }
      setIsDirty(false);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to save note');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="container">
        <button onClick={onBack} style={{ marginBottom: '20px' }} className="btn btn-secondary">
          ← Back
        </button>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          <div className="skeleton skeleton-text" style={{ height: '2rem', width: '60%' }} />
          <div className="skeleton skeleton-text" style={{ height: '400px' }} />
        </div>
      </div>
    );
  }

  return (
    <div className="container" style={{ maxWidth: '900px', margin: '0 auto' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <button onClick={onBack} className="btn btn-secondary">
          ← Back
        </button>
        <button
          onClick={handleSave}
          disabled={!isDirty || saving}
          className="btn btn-primary"
          style={{ opacity: !isDirty || saving ? 0.6 : 1 }}
        >
          {saving ? 'Saving...' : 'Save'}
        </button>
      </div>

      {error && (
        <div className="error-message" style={{ marginBottom: '20px' }}>
          {error}
        </div>
      )}

      <input
        type="text"
        value={title}
        onChange={handleTitleChange}
        placeholder="Note title..."
        maxLength={255}
        style={{
          width: '100%',
          fontSize: '1.5rem',
          fontWeight: 600,
          border: 'none',
          borderBottom: '2px solid var(--color-border)',
          padding: '12px 0 16px 0',
          marginBottom: '24px',
          backgroundColor: 'transparent',
          color: 'var(--color-text)',
          fontFamily: 'var(--font-sans)',
        }}
      />

      <div style={{ minHeight: '400px', marginBottom: '20px' }}>
        <MarkdownEditor
          value={content}
          onChange={handleContentChange}
          placeholder="Note content..."
        />
      </div>

      {note && (
        <div style={{
          marginTop: '32px',
          paddingTop: '16px',
          borderTop: '1px solid var(--color-border)',
          fontSize: '0.75rem',
          color: 'var(--color-text-secondary)',
          display: 'flex',
          justifyContent: 'space-between',
        }}>
          <div>Created: {formatDateTime(note.createdAt)}</div>
          <div>Updated: {formatDateTime(note.updatedAt)}</div>
        </div>
      )}
    </div>
  );
}
