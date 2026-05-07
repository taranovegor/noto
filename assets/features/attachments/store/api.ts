import { api } from '../../../shared/store/api';
import type { AttachmentUploadResponseDto, AttachmentDownloadResponseDto } from '../types';

const attachmentsApi = api.injectEndpoints({
  endpoints: (builder) => ({
    confirmAttachmentUpload: builder.mutation<AttachmentUploadResponseDto, string>({
      query: (attachmentId) => ({
        url: `/attachments/${attachmentId}/confirm`,
        method: 'POST',
      }),
      invalidatesTags: ['Attachments'],
    }),

    getAttachmentDownloadUrl: builder.query<AttachmentDownloadResponseDto, string>({
      query: (attachmentId) => `/attachments/${attachmentId}/download`,
      providesTags: ['Attachments'],
    }),

    getBatchAttachmentDownloadUrl: builder.mutation<
      AttachmentDownloadResponseDto[],
      { ids: string[] }
    >({
      query: (body) => ({
        url: '/attachments/download',
        method: 'POST',
        body,
      }),
    }),
  }),
});

export const {
  useConfirmAttachmentUploadMutation,
  useGetAttachmentDownloadUrlQuery,
  useLazyGetAttachmentDownloadUrlQuery,
  useGetBatchAttachmentDownloadUrlMutation,
} = attachmentsApi;
