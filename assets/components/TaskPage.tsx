import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { TaskResponseDto, TaskStatus, TaskPriority } from '../types/tasks';
import { ProjectResponseDto } from '../types/projects';
import { api } from '../api';
import { STATUS_OPTIONS, PRIORITY_OPTIONS, ColorOption } from '../constants';
import { formatDateTime, toDateInputValue } from '../utils/date';
import { MarkdownEditor } from './MarkdownEditor';

interface FormState {
  name: string;
  status: TaskStatus;
  priority: TaskPriority | undefined;
  deadline: string;
  note: string;
  projectId: string | undefined;
}

function fromTask(task: TaskResponseDto): FormState {
  return {
    name: task.name,
    status: task.status,
    priority: task.priority ?? undefined,
    deadline: task.deadline ? toDateInputValue(task.deadline) : '',
    note: task.note ?? '',
    projectId: task.projectId ?? undefined,
  };
}

function MetaRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div style={{
      display: 'flex', gap: '16px', padding: '14px 0',
      borderBottom: '1px solid var(--color-border)', alignItems: 'center',
    }}>
      <span style={{ width: '120px', flexShrink: 0, fontSize: '0.85rem', color: 'var(--color-text-secondary)', fontWeight: 500 }}>
        {label}
      </span>
      <div style={{ flex: 1 }}>{children}</div>
    </div>
  );
}

function InlineSelect<T extends string>({
  options, value, placeholder, onChange,
}: {
  options: ColorOption<T>[];
  value: T | undefined;
  placeholder: string;
  onChange: (value: T | undefined) => void;
}) {
  return (
    <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
      {!value && (
        <span style={{ fontSize: '0.9rem', color: 'var(--color-text-secondary)', marginRight: '4px' }}>{placeholder}</span>
      )}
      {options.map((opt) => {
        const active = value === opt.value;
        return (
          <button
            key={opt.value}
            type="button"
            onClick={() => onChange(active ? undefined : opt.value)}
            className="inline-select-option"
            style={{
              padding: '4px 12px', borderRadius: '9999px',
              fontSize: '0.75rem', fontWeight: 600,
              letterSpacing: '0.05em', textTransform: 'uppercase',
              cursor: 'pointer', transition: 'all 150ms ease-out',
              border: active ? 'none' : '1px solid var(--color-border)',
              backgroundColor: active ? opt.bg : 'var(--color-bg)',
              color: active ? opt.text : 'var(--color-text-secondary)',
            }}
          >
            {opt.label}
          </button>
        );
      })}
    </div>
  );
}

export function TaskPage() {
  const { id: taskId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isNew = !taskId || taskId === 'new';

  const [task, setTask] = useState<TaskResponseDto | null>(null);
  const [form, setForm] = useState<FormState>({
    name: '', status: 'backlog', priority: undefined, deadline: '', note: '', projectId: undefined,
  });
  const [originalForm, setOriginalForm] = useState<FormState | null>(null);
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [projects, setProjects] = useState<ProjectResponseDto[]>([]);
  const [projectsLoading, setProjectsLoading] = useState(true);
  const nameInputRef = React.useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    api.projects.list()
      .then((response) => {
        setProjects(response.data);
      })
      .catch((err: unknown) => {
        console.error('Failed to load projects:', err);
      })
      .finally(() => setProjectsLoading(false));
  }, []);

  useEffect(() => {
    if (nameInputRef.current) {
      nameInputRef.current.style.height = 'auto';
      nameInputRef.current.style.height = nameInputRef.current.scrollHeight + 'px';
    }
  }, [form.name]);

  // Загрузить задачу или черновик при монтировании
  useEffect(() => {
    if (!isNew) {
      api.tasks.get(taskId!)
        .then((data) => {
          setTask(data);
          const state = fromTask(data);
          setForm(state);
          setOriginalForm(state);
          localStorage.removeItem('taskDraft'); // Очистить черновик если открываем существующую задачу
        })
        .catch((err: unknown) => {
          setError(err instanceof Error ? err.message : 'Failed to load task');
        })
        .finally(() => setLoading(false));
    } else {
      // Новая задача - загрузить черновик если существует
      const savedDraft = localStorage.getItem('taskDraft');
      if (savedDraft) {
        try {
          const draft = JSON.parse(savedDraft);
          setForm(draft);
          setOriginalForm(draft);
        } catch (e) {
          console.error('Failed to load draft:', e);
        }
      }
      setLoading(false);
    }
  }, [taskId]);

  // Сохранять черновик новой задачи в localStorage
  useEffect(() => {
    if (isNew && (form.name || form.projectId || form.note || form.priority || form.deadline)) {
      localStorage.setItem('taskDraft', JSON.stringify(form));
    }
  }, [form, isNew]);

  const isDirty = originalForm !== null && JSON.stringify(form) !== JSON.stringify(originalForm);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name.trim()) { setError('Task name is required'); return; }

    try {
      setSaving(true);
      setError(null);

      if (isNew) {
        const created = await api.tasks.create({
          name: form.name.trim(),
          status: form.status,
          priority: form.priority || undefined,
          deadline: form.deadline || undefined,
          note: form.note || undefined,
          projectId: form.projectId || undefined,
        });
        localStorage.removeItem('taskDraft');
        navigate(`/tasks/${created.id}`);
      } else {
        const updated = await api.tasks.update(taskId!, {
          name: form.name.trim(),
          status: form.status,
          priority: form.priority ?? null,
          deadline: form.deadline || null,
          note: form.note || null,
          projectId: form.projectId ?? null,
        });
        setTask(updated);
        const state = fromTask(updated);
        setForm(state);
        setOriginalForm(state);
      }
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'An error occurred');
    } finally {
      setSaving(false);
    }
  };

  const currentStatus = STATUS_OPTIONS.find((o) => o.value === form.status);

  if (loading) {
    return (
      <div style={{ maxWidth: '720px', animation: 'viewEnter 200ms var(--ease-out) forwards' }}>
        {/* Back button skeleton */}
        <div className="skeleton skeleton-text" style={{ width: '60px', height: '1rem', marginBottom: '32px' }} />

        {/* Title skeleton */}
        <div style={{ marginBottom: '32px' }}>
          <div className="skeleton skeleton-text" style={{ height: '2rem', marginBottom: '12px' }} />
          <div className="skeleton skeleton-text tiny" />
        </div>

        {/* Meta fields skeleton */}
        <div style={{ borderTop: '1px solid var(--color-border)' }}>
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="skeleton-form-row">
              <div className="skeleton skeleton-form-label" />
              <div style={{ flex: 1, display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                <div className="skeleton skeleton-text" style={{ width: '100px', height: '1.5rem' }} />
                <div className="skeleton skeleton-text" style={{ width: '100px', height: '1.5rem' }} />
              </div>
            </div>
          ))}
        </div>

        {/* Note section skeleton */}
        <div style={{ marginTop: '40px' }}>
          <div className="skeleton skeleton-text" style={{ width: '80px', height: '0.8rem', marginBottom: '16px' }} />
          <div className="skeleton skeleton-text" style={{ height: '160px' }} />
        </div>
      </div>
    );
  }

  if (error && !isNew && !task) {
    return (
      <div className="error-message">
        {error}
      </div>
    );
  }

  return (
    <form onSubmit={handleSave} style={{ maxWidth: '720px', animation: 'viewEnter 200ms var(--ease-out) forwards' }}>
      <button type="button" onClick={() => navigate('/tasks')} className="btn-ghost" style={{ marginBottom: '32px', display: 'inline-flex', alignItems: 'center' }}>
        ← Back
      </button>

      <div style={{ marginBottom: '32px' }}>
        <textarea
          ref={nameInputRef}
          value={form.name}
          onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
          placeholder="Task name"
          autoFocus={isNew}
          className="input-ghost"
          style={{
            width: '100%', fontSize: '1.875rem', fontFamily: 'var(--font-serif)',
            fontWeight: 600, letterSpacing: '-0.02em', lineHeight: 1.2,
            color: 'var(--color-text)', marginBottom: '12px', resize: 'none',
            overflow: 'hidden', minHeight: 'calc(1.875rem * 1.2 + 2px)',
          }}
          rows={1}
        />
        {task?.code && (
          <p className="text-mono" style={{ marginBottom: 0, color: 'var(--color-text-secondary)' }}>{task.code}</p>
        )}
      </div>

      <div style={{ borderTop: '1px solid var(--color-border)' }}>
        <MetaRow label="Status">
          <InlineSelect
            options={STATUS_OPTIONS}
            value={form.status}
            placeholder="Select status"
            onChange={(v) => setForm((p) => ({ ...p, status: v ?? 'backlog' }))}
          />
        </MetaRow>

        <MetaRow label="Priority">
          <InlineSelect
            options={PRIORITY_OPTIONS}
            value={form.priority}
            placeholder="No priority"
            onChange={(v) => setForm((p) => ({ ...p, priority: v }))}
          />
        </MetaRow>

        <MetaRow label="Deadline">
          <input
            type="date"
            value={form.deadline}
            onChange={(e) => setForm((p) => ({ ...p, deadline: e.target.value }))}
            className="input-ghost"
            style={{
              fontSize: '0.95rem', cursor: 'pointer',
              color: form.deadline ? 'var(--color-text)' : 'var(--color-text-secondary)',
            }}
          />
        </MetaRow>

        <MetaRow label="Project">
          <select
            value={form.projectId ?? ''}
            onChange={(e) => setForm((p) => ({ ...p, projectId: e.target.value || undefined }))}
            disabled={projectsLoading}
            className="input-ghost"
            style={{
              fontSize: '0.95rem', cursor: 'pointer',
              color: form.projectId ? 'var(--color-text)' : 'var(--color-text-secondary)',
              padding: '6px 8px', borderRadius: '4px', border: '1px solid var(--color-border)',
            }}
          >
            <option value="">No project</option>
            {projects.map((project) => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
        </MetaRow>
      </div>

      <div style={{ marginTop: '40px' }}>
        <p style={{
          fontSize: '0.85rem', color: 'var(--color-text-secondary)', fontWeight: 500,
          marginBottom: '16px', textTransform: 'uppercase', letterSpacing: '0.05em',
        }}>
          Note
        </p>
        <MarkdownEditor
          value={form.note}
          onChange={(markdown) => setForm((p) => ({ ...p, note: markdown }))}
          placeholder="Add notes..."
        />
      </div>

      {error && (
        <div className="error-message" style={{ marginTop: '24px' }}>
          {error}
        </div>
      )}

      {(isNew || isDirty) && (
        <div style={{ marginTop: '40px', paddingTop: '24px', borderTop: '1px solid var(--color-border)' }}>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Saving...' : isNew ? 'Create task' : 'Save changes'}
          </button>
        </div>
      )}
    </form>
  );
}
