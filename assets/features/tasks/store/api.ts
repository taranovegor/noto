import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type { TaskResponseDto, CreateTaskDto, UpdateTaskDto, TaskStatus } from '../types';

const PAGE_SIZE = 20;

const tasksApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getTasks: builder.infiniteQuery<
      TaskResponseDto[],
      { search?: string | null; projectId?: string | null; status?: TaskStatus | null } | void,
      number
    >({
      infiniteQueryOptions: {
        initialPageParam: 0,
        getNextPageParam: (lastPage, _allPages, lastPageParam) => {
          if (lastPage.length < PAGE_SIZE) return undefined;
          return lastPageParam + PAGE_SIZE;
        },
      },
      keepUnusedDataFor: 1800,
      query: ({ queryArg = {}, pageParam: offset }) => {
        const { search, projectId, status } = queryArg;
        const filters: string[] = [];
        if (search) filters.push(`filter[query]=${encodeURIComponent(search)}`);
        if (projectId) filters.push(`filter[projectId]=${encodeURIComponent(projectId)}`);
        if (status) filters.push(`filter[status]=${encodeURIComponent(status)}`);
        const filterQs = filters.length ? `&${filters.join('&')}` : '';
        return `/tasks?limit=${PAGE_SIZE}&offset=${offset}&sort=-updatedAt${filterQs}`;
      },
      transformResponse: (res: ListResponse<TaskResponseDto>) => res.data,
      providesTags: ['Tasks'],
    }),

    getTask: builder.query<TaskResponseDto, string>({
      query: (id) => `/tasks/${id}`,
      providesTags: (_, __, id) => [{ type: 'Tasks', id }],
    }),

    createTask: builder.mutation<TaskResponseDto, CreateTaskDto>({
      query: (body) => ({ url: '/tasks', method: 'POST', body }),
      invalidatesTags: ['Tasks'],
    }),

    updateTask: builder.mutation<TaskResponseDto, { id: string; body: UpdateTaskDto }>({
      query: ({ id, body }) => ({ url: `/tasks/${id}`, method: 'PATCH', body }),
      invalidatesTags: (_, __, { id }) => [{ type: 'Tasks', id }, 'Tasks'],
      async onQueryStarted({ id, body }, { dispatch, queryFulfilled }) {
        const patchResult = dispatch(
          tasksApi.util.updateQueryData('getTasks', { search: null, projectId: null }, (draft) => {
            for (const page of draft.pages) {
              const task = page.find((t) => t.id === id);
              if (task) {
                Object.assign(task, body);
                return;
              }
            }
          }),
        );
        try {
          await queryFulfilled;
        } catch {
          patchResult.undo();
        }
      },
    }),

    attachTaskAttachments: builder.mutation<TaskResponseDto, { id: string; attachments: string[] }>(
      {
        query: ({ id, attachments }) => ({
          url: `/tasks/${id}/attachments`,
          method: 'POST',
          body: { attachments },
        }),
        invalidatesTags: (_, __, { id }) => [{ type: 'Tasks', id }],
      },
    ),

    detachTaskAttachment: builder.mutation<void, { id: string; attachmentId: string }>({
      query: ({ id, attachmentId }) => ({
        url: `/tasks/${id}/attachments/${attachmentId}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_, __, { id }) => [{ type: 'Tasks', id }],
    }),
  }),
});

export const {
  useGetTasksInfiniteQuery: useTasks,
  useGetTaskQuery,
  useCreateTaskMutation,
  useUpdateTaskMutation,
  useAttachTaskAttachmentsMutation,
  useDetachTaskAttachmentMutation,
} = tasksApi;
