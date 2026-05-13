import type { AttachmentResponseDto } from '../attachments';

export type TaskStatus = 'backlog' | 'in_progress' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high';

export interface TaskResponseDto {
  id: string;
  projectId?: string | null;
  code?: string | null;
  name: string;
  status: TaskStatus;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string | null;
  attachments?: AttachmentResponseDto[] | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateTaskDto {
  projectId?: string | null;
  name: string;
  status: TaskStatus;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string;
  attachments?: string[] | null;
}

export interface UpdateTaskDto {
  projectId?: string | null;
  name?: string | null;
  status?: TaskStatus | null;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string | null;
}
