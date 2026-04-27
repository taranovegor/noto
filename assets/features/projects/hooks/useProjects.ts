import { useGetProjectsQuery } from '../store/api';

export function useProjects() {
  return useGetProjectsQuery();
}
