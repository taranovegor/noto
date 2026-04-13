import { TaskResponseDto, CreateTaskDto, UpdateTaskDto, ListResponse } from '../types/api';
import { request } from './request';

export const tasks = {
  list: (limit = 100) =>
    request<ListResponse<TaskResponseDto>>(`/api/tasks?limit=${limit}`),

  get: (id: string) =>
    request<TaskResponseDto>(`/api/tasks/${id}`),

  create: (dto: CreateTaskDto) =>
    request<TaskResponseDto>('/api/tasks', {
      method: 'POST',
      body: JSON.stringify(dto),
    }),

  update: (id: string, dto: UpdateTaskDto) =>
    request<TaskResponseDto>(`/api/tasks/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(dto),
    }),
};
