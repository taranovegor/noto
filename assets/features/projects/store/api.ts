import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type { ProjectResponseDto } from '../types';

const projectsApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getProjects: builder.query<ProjectResponseDto[], void>({
      query: () => '/projects',
      transformResponse: (res: ListResponse<ProjectResponseDto>) => res.data,
      providesTags: ['Projects'],
    }),
  }),
});

export const { useGetProjectsQuery } = projectsApi;
