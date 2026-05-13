import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type { NoteResponseDto, CreateNoteDto, UpdateNoteDto } from '../types';

const PAGE_SIZE = 10;

const notesApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getNotes: builder.infiniteQuery<
      { notes: NoteResponseDto[]; total: number },
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
        return `/notes${params}`;
      },
      transformResponse: (res: ListResponse<NoteResponseDto>) => ({
        notes: res.data,
        total: res.pagination.total,
      }),
      providesTags: ['Notes'],
    }),

    getNote: builder.query<NoteResponseDto, string>({
      query: (id) => `/notes/${id}`,
      providesTags: (_, __, id) => [{ type: 'Notes', id }],
    }),

    createNote: builder.mutation<NoteResponseDto, CreateNoteDto>({
      query: (body) => ({ url: '/notes', method: 'POST', body }),
      invalidatesTags: ['Notes'],
    }),

    attachNoteAttachments: builder.mutation<
      NoteResponseDto,
      { noteId: string; attachments: string[] }
    >({
      query: ({ noteId, attachments }) => ({
        url: `/notes/${noteId}/attachments`,
        method: 'POST',
        body: { attachments },
      }),
      invalidatesTags: (_, __, { noteId }) => [{ type: 'Notes', id: noteId }],
    }),

    detachNoteAttachment: builder.mutation<void, { noteId: string; attachmentId: string }>({
      query: ({ noteId, attachmentId }) => ({
        url: `/notes/${noteId}/attachments/${attachmentId}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_, __, { noteId }) => [{ type: 'Notes', id: noteId }],
    }),

    updateNote: builder.mutation<NoteResponseDto, { id: string; body: UpdateNoteDto }>({
      query: ({ id, body }) => ({ url: `/notes/${id}`, method: 'PATCH', body }),
      async onQueryStarted({ id, body }, { dispatch, queryFulfilled }) {
        const listPatch = dispatch(
          notesApi.util.updateQueryData('getNotes', null, (draft) => {
            for (const page of draft.pages) {
              const note = page.notes.find((n) => n.id === id);
              if (note) {
                Object.assign(note, body);
                return;
              }
            }
          }),
        );
        try {
          const { data } = await queryFulfilled;
          dispatch(notesApi.util.updateQueryData('getNote', id, () => data));
          dispatch(
            notesApi.util.updateQueryData('getNotes', null, (draft) => {
              for (const page of draft.pages) {
                const note = page.notes.find((n) => n.id === id);
                if (note) {
                  Object.assign(note, data);
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
  useGetNotesInfiniteQuery: useNotes,
  useGetNoteQuery,
  useCreateNoteMutation,
  useUpdateNoteMutation,
  useAttachNoteAttachmentsMutation,
  useDetachNoteAttachmentMutation,
} = notesApi;
