import { TaskStatus, TaskPriority } from './types';
import { ColorOption } from '../../shared/types';

export const STATUS_OPTIONS: ColorOption<TaskStatus>[] = [
  { value: 'backlog', label: 'Backlog', bg: '#fdebec', text: '#9f2f2d' },
  { value: 'in_progress', label: 'In Progress', bg: '#e1f3fe', text: '#1f6c9f' },
  { value: 'done', label: 'Done', bg: '#edf3ec', text: '#346538' },
];

export const PRIORITY_OPTIONS: ColorOption<TaskPriority>[] = [
  { value: 'low', label: 'Low', bg: '#eef5e9', text: '#2d6a2d' },
  { value: 'medium', label: 'Medium', bg: '#fbf3db', text: '#956400' },
  { value: 'high', label: 'High', bg: '#fdebec', text: '#9f2f2d' },
];

export const STATUS_LABEL_MAP = new Map(STATUS_OPTIONS.map((o) => [o.value, o.label]));
export const PRIORITY_LABEL_MAP = new Map(PRIORITY_OPTIONS.map((o) => [o.value, o.label]));

export const TASK_DRAFT_KEY = 'taskDraft';
