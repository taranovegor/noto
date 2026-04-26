import { NoteResponseDto, CreateNoteDto, UpdateNoteDto, ListResponse } from '../types/api';
import { request } from './request';

export const notes = {
  list: (limit = 100, offset = 0) =>
    request<ListResponse<NoteResponseDto>>(`/api/notes?limit=${limit}&offset=${offset}&sort=-updatedAt`),

  get: (id: string) =>
    request<NoteResponseDto>(`/api/notes/${id}`),

  create: (dto: CreateNoteDto) =>
    request<NoteResponseDto>('/api/notes', {
      method: 'POST',
      body: JSON.stringify(dto),
    }),

  update: (id: string, dto: UpdateNoteDto) =>
    request<NoteResponseDto>(`/api/notes/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(dto),
    }),
};
