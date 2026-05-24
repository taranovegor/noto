import { api } from '../../../shared/store/api';
import type { ListResponse } from '../../../shared/types/api';
import type {
  NotebookResponseDto,
  CreateNotebookDto,
  UpdateNotebookDto,
  NoteResponseDto,
  CreateNoteDto,
  UpdateNoteDto,
} from '../types';

const NOTEBOOK_PAGE_SIZE = 10;
const NOTE_PAGE_SIZE = 10;

const notebooksApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getNotebooks: builder.infiniteQuery<
      { notebooks: NotebookResponseDto[]; total: number },
      string | null,
      number
    >({
      infiniteQueryOptions: {
        initialPageParam: 0,
        getNextPageParam: (lastPage, _allPages, lastPageParam) => {
          const nextOffset = lastPageParam + NOTEBOOK_PAGE_SIZE;
          if (nextOffset >= lastPage.total) return undefined;
          return nextOffset;
        },
      },
      keepUnusedDataFor: 1800,
      query: ({ queryArg: search, pageParam: offset }) => {
        const params = search
          ? `?filter[query]=${encodeURIComponent(search)}&limit=${NOTEBOOK_PAGE_SIZE}&offset=${offset}&sort=title`
          : `?limit=${NOTEBOOK_PAGE_SIZE}&offset=${offset}&sort=title`;
        return `/notebooks${params}`;
      },
      transformResponse: (res: ListResponse<NotebookResponseDto>) => ({
        notebooks: res.data,
        total: res.pagination.total,
      }),
      providesTags: ['Notebooks'],
    }),

    getNotebook: builder.query<NotebookResponseDto, string>({
      query: (id) => `/notebooks/${id}`,
      providesTags: (_, __, id) => [{ type: 'Notebooks', id }],
    }),

    createNotebook: builder.mutation<NotebookResponseDto, CreateNotebookDto>({
      query: (body) => ({ url: '/notebooks', method: 'POST', body }),
      invalidatesTags: ['Notebooks'],
    }),

    updateNotebook: builder.mutation<NotebookResponseDto, { id: string; body: UpdateNotebookDto }>({
      query: ({ id, body }) => ({ url: `/notebooks/${id}`, method: 'PATCH', body }),
      async onQueryStarted({ id, body }, { dispatch, queryFulfilled }) {
        const listPatch = dispatch(
          notebooksApi.util.updateQueryData('getNotebooks', null, (draft) => {
            for (const page of draft.pages) {
              const nb = page.notebooks.find((n) => n.id === id);
              if (nb) {
                Object.assign(nb, body);
                return;
              }
            }
          }),
        );
        try {
          const { data } = await queryFulfilled;
          dispatch(notebooksApi.util.updateQueryData('getNotebook', id, () => data));
          dispatch(
            notebooksApi.util.updateQueryData('getNotebooks', null, (draft) => {
              for (const page of draft.pages) {
                const nb = page.notebooks.find((n) => n.id === id);
                if (nb) {
                  Object.assign(nb, data);
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

    getNotes: builder.infiniteQuery<
      { notes: NoteResponseDto[]; total: number },
      { notebookId: string; search: string | null },
      number
    >({
      infiniteQueryOptions: {
        initialPageParam: 0,
        getNextPageParam: (lastPage, _allPages, lastPageParam) => {
          const nextOffset = lastPageParam + NOTE_PAGE_SIZE;
          if (nextOffset >= lastPage.total) return undefined;
          return nextOffset;
        },
      },
      keepUnusedDataFor: 1800,
      query: ({ queryArg, pageParam: offset }) => {
        const { notebookId, search } = queryArg;
        const base = `/notebooks/${notebookId}/notes`;
        const params = search
          ? `?filter[query]=${encodeURIComponent(search)}&limit=${NOTE_PAGE_SIZE}&offset=${offset}&sort=title`
          : `?limit=${NOTE_PAGE_SIZE}&offset=${offset}&sort=title`;
        return `${base}${params}`;
      },
      transformResponse: (res: ListResponse<NoteResponseDto>) => ({
        notes: res.data,
        total: res.pagination.total,
      }),
      providesTags: ['Notes'],
    }),

    getNote: builder.query<NoteResponseDto, { notebookId: string; noteId: string }>({
      query: ({ notebookId, noteId }) => `/notebooks/${notebookId}/notes/${noteId}`,
      providesTags: (_, __, { noteId }) => [{ type: 'Notes', id: noteId }],
    }),

    createNote: builder.mutation<NoteResponseDto, { notebookId: string; body: CreateNoteDto }>({
      query: ({ notebookId, body }) => ({
        url: `/notebooks/${notebookId}/notes`,
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Notes'],
    }),

    updateNote: builder.mutation<
      NoteResponseDto,
      { notebookId: string; noteId: string; body: UpdateNoteDto }
    >({
      query: ({ notebookId, noteId, body }) => ({
        url: `/notebooks/${notebookId}/notes/${noteId}`,
        method: 'PATCH',
        body,
      }),
      async onQueryStarted({ notebookId, noteId, body }, { dispatch, queryFulfilled }) {
        const listPatch = dispatch(
          notebooksApi.util.updateQueryData('getNotes', { notebookId, search: null }, (draft) => {
            for (const page of draft.pages) {
              const note = page.notes.find((n) => n.id === noteId);
              if (note) {
                Object.assign(note, body);
                return;
              }
            }
          }),
        );
        try {
          const { data } = await queryFulfilled;
          dispatch(
            notebooksApi.util.updateQueryData('getNote', { notebookId, noteId }, () => data),
          );
          dispatch(
            notebooksApi.util.updateQueryData('getNotes', { notebookId, search: null }, (draft) => {
              for (const page of draft.pages) {
                const note = page.notes.find((n) => n.id === noteId);
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

    attachNoteAttachments: builder.mutation<
      NoteResponseDto,
      { notebookId: string; noteId: string; attachments: string[] }
    >({
      query: ({ notebookId, noteId, attachments }) => ({
        url: `/notebooks/${notebookId}/notes/${noteId}/attachments`,
        method: 'POST',
        body: { attachments },
      }),
      invalidatesTags: (_, __, { noteId }) => [{ type: 'Notes', id: noteId }],
    }),

    detachNoteAttachment: builder.mutation<
      void,
      { notebookId: string; noteId: string; attachmentId: string }
    >({
      query: ({ notebookId, noteId, attachmentId }) => ({
        url: `/notebooks/${notebookId}/notes/${noteId}/attachments/${attachmentId}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_, __, { noteId }) => [{ type: 'Notes', id: noteId }],
    }),
  }),
});

export const {
  useGetNotebooksInfiniteQuery: useNotebooks,
  useGetNotebookQuery,
  useCreateNotebookMutation,
  useUpdateNotebookMutation,
  useGetNotesInfiniteQuery: useNotes,
  useGetNoteQuery,
  useCreateNoteMutation,
  useUpdateNoteMutation,
  useAttachNoteAttachmentsMutation,
  useDetachNoteAttachmentMutation,
} = notebooksApi;
