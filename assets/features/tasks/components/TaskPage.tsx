import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Paperclip } from 'lucide-react';
import type { TaskResponseDto, TaskStatus, TaskPriority } from '../types';
import {
  useGetTaskQuery,
  useCreateTaskMutation,
  useUpdateTaskMutation,
  useAttachTaskAttachmentsMutation,
  useDetachTaskAttachmentMutation,
} from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';
import type { AttachmentResponseDto } from '../../attachments';
import { useProjects } from '../../projects/hooks/useProjects';
import { STATUS_OPTIONS, PRIORITY_OPTIONS, TASK_DRAFT_KEY } from '../constants';
import { toDateInputValue } from '../../../shared/utils';
import {
  MarkdownEditor,
  MetaRow,
  InlineSelect,
  FormShell,
  AttachmentsList,
} from '../../../shared/components';
import { useAutoResize, useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
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
  const [attachTaskAttachments] = useAttachTaskAttachmentsMutation();
  const [detachTaskAttachment] = useDetachTaskAttachmentMutation();
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmAttachmentUpload] = useConfirmAttachmentUploadMutation();

  const [form, setForm] = useState<FormState>({
    name: '',
    status: 'backlog',
    priority: undefined,
    deadline: '',
    note: '',
    projectId: undefined,
  });
  const [saveError, setSaveError] = useState<string | null>(null);
  const [pendingAttachments, setPendingAttachments] = useState<AttachmentResponseDto[]>([]);
  const [attachUploading, setAttachUploading] = useState(false);
  const nameInputRef = useRef<HTMLTextAreaElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const loading = taskLoading;
  const saving = isCreating || isUpdating;

  const attachments = isNew ? pendingAttachments : (task?.attachments ?? []);

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
          attachments: pendingAttachments.map((a) => a.id),
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

  const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    e.target.value = '';

    setAttachUploading(true);
    setSaveError(null);

    try {
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

      const confirmed = await confirmAttachmentUpload(id).unwrap();

      if (isNew) {
        setPendingAttachments((prev) => [...prev, confirmed]);
      } else {
        await attachTaskAttachments({ id: taskId!, attachments: [id] }).unwrap();
      }
    } catch (err: unknown) {
      setSaveError(parseError(err).message || 'Failed to upload attachment');
    } finally {
      setAttachUploading(false);
    }
  };

  const handleDetach = async (attachmentId: string) => {
    if (isNew) {
      setPendingAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
      return;
    }

    setSaveError(null);
    try {
      await detachTaskAttachment({ id: taskId!, attachmentId }).unwrap();
    } catch (err: unknown) {
      setSaveError(parseError(err).message);
    }
  };

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => (history.length > 1 ? navigate(-1) : navigate('/tasks')),
    },
    buttons: [
      {
        icon: Save,
        label: 'Save',
        primary: true,
        disabled: saving || attachUploading || (!isNew && !isDirty),
        onPress: () => void handleSave({ preventDefault: () => {} } as React.SyntheticEvent),
      },
      {
        icon: Paperclip,
        label: 'Attach file',
        disabled: attachUploading,
        onPress: () => fileInputRef.current?.click(),
      },
    ],
  });

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
      extraActions={
        <button
          type="button"
          className="btn"
          onClick={() => fileInputRef.current?.click()}
          disabled={attachUploading}
        >
          <Paperclip size={16} strokeWidth={1.75} />
          <span style={{ marginLeft: 6 }}>Attach file</span>
        </button>
      }
    >
      <div className={styles.titleRow}>
        <div className={styles.nameColumn}>
          <textarea
            ref={nameInputRef}
            value={form.name}
            onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
            placeholder="Untitled task"
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

        <MetaRow label="Project" divider={false}>
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
        <MarkdownEditor
          value={form.note}
          onChange={(markdown) => setForm((p) => ({ ...p, note: markdown }))}
          placeholder="Start writing..."
        />
      </div>

      <input
        ref={fileInputRef}
        type="file"
        style={{ display: 'none' }}
        onChange={handleFileSelect}
      />

      <AttachmentsList
        attachments={attachments}
        uploading={attachUploading}
        onRemove={handleDetach}
      />
    </FormShell>
  );
}
