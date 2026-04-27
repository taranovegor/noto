import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { TaskResponseDto, TaskStatus, TaskPriority } from '../types';
import { useGetTaskQuery, useCreateTaskMutation, useUpdateTaskMutation } from '../store/api';
import { useProjects } from '../../projects/hooks/useProjects';
import { STATUS_OPTIONS, PRIORITY_OPTIONS, TASK_DRAFT_KEY } from '../constants';
import { toDateInputValue } from '../../../shared/utils';
import { MarkdownEditor, MetaRow, InlineSelect, FormShell } from '../../../shared/components';
import { useAutoResize, useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { parseError } from '../../../shared/utils';
import { TaskPageSkeleton } from './TaskPageSkeleton';
import styles from './TaskPage.module.css';

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

export function TaskPage() {
  const { id: taskId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isNew = !taskId || taskId === 'new';

  const {
    data: task,
    isLoading: taskLoading,
    error: taskError,
  } = useGetTaskQuery(taskId ?? '', { skip: isNew || !taskId });
  const { data: projects = [] } = useProjects();
  const [createTask, { isLoading: isCreating }] = useCreateTaskMutation();
  const [updateTask, { isLoading: isUpdating }] = useUpdateTaskMutation();

  const [form, setForm] = useState<FormState>({
    name: '',
    status: 'backlog',
    priority: undefined,
    deadline: '',
    note: '',
    projectId: undefined,
  });
  const [saveError, setSaveError] = useState<string | null>(null);
  const nameInputRef = React.useRef<HTMLTextAreaElement>(null);

  const loading = taskLoading;
  const saving = isCreating || isUpdating;

  useAutoResize(nameInputRef, form.name);
  const { restore, clear } = useDraftSave(TASK_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  useEffect(() => {
    if (task) {
      const state = fromTask(task);
      setForm(state);
      markSaved(state);
      localStorage.removeItem(TASK_DRAFT_KEY);
    }
  }, [task, markSaved]);

  useDraftRestore(isNew, restore, setForm, markSaved);

  const dateInputStyle = {
    '--input-color': form.deadline ? 'var(--color-text)' : 'var(--color-text-secondary)',
  };

  const projectSelectStyle = {
    '--select-color': form.projectId ? 'var(--color-text)' : 'var(--color-text-secondary)',
  };

  const handleSave = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!form.name.trim()) {
      setSaveError('Task name is required');
      return;
    }

    try {
      setSaveError(null);

      if (isNew) {
        const created = await createTask({
          name: form.name.trim(),
          status: form.status,
          priority: form.priority || undefined,
          deadline: form.deadline || undefined,
          note: form.note || undefined,
          projectId: form.projectId || undefined,
        }).unwrap();
        clear();
        navigate(`/tasks/${created.id}`);
      } else {
        if (!taskId) return;
        const updated = await updateTask({
          id: taskId,
          body: {
            name: form.name.trim(),
            status: form.status,
            priority: form.priority ?? null,
            deadline: form.deadline || null,
            note: form.note || null,
            projectId: form.projectId ?? null,
          },
        }).unwrap();
        const state = fromTask(updated);
        setForm(state);
        markSaved(state);
      }
    } catch (err: unknown) {
      setSaveError(parseError(err).message);
    }
  };

  if (loading) {
    return <TaskPageSkeleton />;
  }

  if ((taskError || saveError) && !isNew && !task) {
    return (
      <div className="error-message" role="alert">
        {taskError ? 'Failed to load task' : saveError}
      </div>
    );
  }

  return (
    <FormShell
      backTo="/tasks"
      error={saveError}
      saving={saving}
      showSaveBar={isNew || isDirty}
      onSubmit={handleSave}
    >
      <div className={styles.titleRow}>
        <div className={styles.nameColumn}>
          <textarea
            ref={nameInputRef}
            value={form.name}
            onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
            placeholder="Task"
            autoFocus={isNew}
            className={styles.nameInput}
            rows={1}
          />
          {task?.code && <p className={styles.code}>{task.code}</p>}
        </div>
      </div>

      <div className={styles.metaSection}>
        <MetaRow label="Status">
          <InlineSelect
            options={STATUS_OPTIONS}
            value={form.status}
            emptyLabel="Select status"
            onChange={(v) => setForm((p) => ({ ...p, status: v ?? 'backlog' }))}
          />
        </MetaRow>

        <MetaRow label="Priority">
          <InlineSelect
            options={PRIORITY_OPTIONS}
            value={form.priority}
            emptyLabel="No priority"
            onChange={(v) => setForm((p) => ({ ...p, priority: v }))}
          />
        </MetaRow>

        <MetaRow label="Deadline">
          <input
            type="date"
            value={form.deadline}
            onChange={(e) => setForm((p) => ({ ...p, deadline: e.target.value }))}
            className={`input-ghost ${styles.dateInput}`}
            style={dateInputStyle}
          />
        </MetaRow>

        <MetaRow label="Project">
          <select
            value={form.projectId ?? ''}
            onChange={(e) => setForm((p) => ({ ...p, projectId: e.target.value || undefined }))}
            className={styles.projectSelect}
            style={projectSelectStyle}
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

      <div className={styles.noteSection}>
        <p className={styles.noteLabel}>Note</p>
        <MarkdownEditor
          value={form.note}
          onChange={(markdown) => setForm((p) => ({ ...p, note: markdown }))}
          placeholder="Write..."
        />
      </div>
    </FormShell>
  );
}
