import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type {
  StashResponseDto,
  CreateStashResponseDto,
  CreateStashDto,
  UpdateStashDto,
} from '../types';

const stashesApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getStashes: builder.query<
      { data: StashResponseDto[]; pagination: { limit: number; offset: number; total: number } },
      {
        filterActive?: boolean;
        limit?: number;
        offset?: number;
      }
    >({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params.filterActive !== undefined) {
          searchParams.append('filter[active]', params.filterActive ? 'true' : 'false');
        }
        searchParams.append('limit', String(params.limit ?? 100));
        searchParams.append('offset', String(params.offset ?? 0));
        searchParams.append('sort', '-pinned;-updatedAt');

        return `/stashes?${searchParams.toString()}`;
      },
      transformResponse: (res: ListResponse<StashResponseDto>) => ({
        data: res.data,
        pagination: res.pagination,
      }),
      providesTags: ['Stashes'],
    }),

    createStash: builder.mutation<CreateStashResponseDto, CreateStashDto>({
      query: (body) => ({
        url: '/stashes',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Stashes'],
    }),

    updateStash: builder.mutation<StashResponseDto, { id: string; body: UpdateStashDto }>({
      query: ({ id, body }) => ({
        url: `/stashes/${id}`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: (_, __, { id }) => [{ type: 'Stashes', id }, 'Stashes'],
    }),
  }),
});

export const { useGetStashesQuery, useCreateStashMutation, useUpdateStashMutation } = stashesApi;
