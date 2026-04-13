import { ProjectResponseDto, ListResponse } from '../types/api';
import { request } from './request';

export const projects = {
  list: (limit = 100) =>
    request<ListResponse<ProjectResponseDto>>(`/api/projects?limit=${limit}`),
};
