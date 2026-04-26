import { ProjectResponseDto } from '../types/projects';
import { ListResponse } from '../types/api';
import { request } from './request';

export const projects = {
  list: (limit = 100) =>
    request<ListResponse<ProjectResponseDto>>(`/api/projects?limit=${limit}`),
};
