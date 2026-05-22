import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type { MemoResponseDto, CreateMemoDto, UpdateMemoDto } from '../types';

const PAGE_SIZE = 10;

const memosApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getMemos: builder.infiniteQuery<
      { memos: MemoResponseDto[]; total: number },
      string | null,
      number
    >({
      infiniteQueryOptions: {
        initialPageParam: 0,
        getNextPageParam: (lastPage, _allPages, lastPageParam) => {
          const nextOffset = lastPageParam + PAGE_SIZE;
          if (nextOffset >= lastPage.total) return undefined;
          return nextOffset;
        },
      },
      keepUnusedDataFor: 1800,
      query: ({ queryArg: search, pageParam: offset }) => {
        const params = search
          ? `?filter[query]=${encodeURIComponent(search)}&limit=${PAGE_SIZE}&offset=${offset}&sort=-updatedAt`
          : `?limit=${PAGE_SIZE}&offset=${offset}&sort=-updatedAt`;
        return `/memos${params}`;
      },
      transformResponse: (res: ListResponse<MemoResponseDto>) => ({
        memos: res.data,
        total: res.pagination.total,
      }),
      providesTags: ['Memos'],
    }),

    getMemo: builder.query<MemoResponseDto, string>({
      query: (id) => `/memos/${id}`,
      providesTags: (_, __, id) => [{ type: 'Memos', id }],
    }),

    createMemo: builder.mutation<MemoResponseDto, CreateMemoDto>({
      query: (body) => ({ url: '/memos', method: 'POST', body }),
      invalidatesTags: ['Memos'],
    }),

    attachMemoAttachments: builder.mutation<
      MemoResponseDto,
      { memoId: string; attachments: string[] }
    >({
      query: ({ memoId, attachments }) => ({
        url: `/memos/${memoId}/attachments`,
        method: 'POST',
        body: { attachments },
      }),
      invalidatesTags: (_, __, { memoId }) => [{ type: 'Memos', id: memoId }],
    }),

    detachMemoAttachment: builder.mutation<void, { memoId: string; attachmentId: string }>({
      query: ({ memoId, attachmentId }) => ({
        url: `/memos/${memoId}/attachments/${attachmentId}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_, __, { memoId }) => [{ type: 'Memos', id: memoId }],
    }),

    updateMemo: builder.mutation<MemoResponseDto, { id: string; body: UpdateMemoDto }>({
      query: ({ id, body }) => ({ url: `/memos/${id}`, method: 'PATCH', body }),
      async onQueryStarted({ id, body }, { dispatch, queryFulfilled }) {
        const listPatch = dispatch(
          memosApi.util.updateQueryData('getMemos', null, (draft) => {
            for (const page of draft.pages) {
              const memo = page.memos.find((m) => m.id === id);
              if (memo) {
                Object.assign(memo, body);
                return;
              }
            }
          }),
        );
        try {
          const { data } = await queryFulfilled;
          dispatch(memosApi.util.updateQueryData('getMemo', id, () => data));
          dispatch(
            memosApi.util.updateQueryData('getMemos', null, (draft) => {
              for (const page of draft.pages) {
                const memo = page.memos.find((m) => m.id === id);
                if (memo) {
                  Object.assign(memo, data);
                  return;
                }
              }
            }),
          );
        } catch {
          listPatch.undo();
        }
      },
    }),
  }),
});

export const {
  useGetMemosInfiniteQuery: useMemos,
  useGetMemoQuery,
  useCreateMemoMutation,
  useUpdateMemoMutation,
  useAttachMemoAttachmentsMutation,
  useDetachMemoAttachmentMutation,
} = memosApi;
