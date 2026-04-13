export type TaskStatus = 'backlog' | 'in_progress' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high';

export interface TaskResponseDto {
  id: string;
  project_id?: string | null;
  code?: string | null;
  name: string;
  status: TaskStatus;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string | null;
  created_at: string;
  updated_at: string;
}

export interface CreateTaskDto {
  project_id?: string | null;
  name: string;
  status: TaskStatus;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string;
}

export interface UpdateTaskDto {
  project_id?: string | null;
  name?: string | null;
  status?: TaskStatus | null;
  priority?: TaskPriority | null;
  deadline?: string | null;
  note?: string | null;
}

export interface ProjectResponseDto {
  id: string;
  name: string;
  prefix: string;
  aliases: Record<string, unknown> | unknown[];
  created_at: string;
}

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

export interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}
