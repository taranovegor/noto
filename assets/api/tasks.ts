import { TaskResponseDto, CreateTaskDto, UpdateTaskDto, ListResponse } from '../types/api';
import { request } from './request';

export const tasks = {
  list: (limit = 100) =>
    request<ListResponse<TaskResponseDto>>(`/api/tasks?limit=${limit}`),

  search: (query: string, limit = 100, offset = 0) =>
    request<ListResponse<TaskResponseDto>>(`/api/tasks?filter[query]=${encodeURIComponent(query)}&limit=${limit}&offset=${offset}`),

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
