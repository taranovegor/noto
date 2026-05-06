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
  }),
});

export const {
  useConfirmAttachmentUploadMutation,
  useGetAttachmentDownloadUrlQuery,
  useLazyGetAttachmentDownloadUrlQuery,
} = attachmentsApi;
