import { api } from '../../../shared/store/api';
import type {
  AttachmentDto,
  AttachmentResponseDto,
  AttachmentUploadResponseDto,
  AttachmentDownloadResponseDto,
} from '../types';

const attachmentsApi = api.injectEndpoints({
  endpoints: (builder) => ({
    createAttachment: builder.mutation<AttachmentUploadResponseDto, AttachmentDto>({
      query: (body) => ({ url: '/attachments', method: 'POST', body }),
    }),

    confirmAttachmentUpload: builder.mutation<AttachmentResponseDto, string>({
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
  useCreateAttachmentMutation,
  useConfirmAttachmentUploadMutation,
  useGetAttachmentDownloadUrlQuery,
  useLazyGetAttachmentDownloadUrlQuery,
  useGetBatchAttachmentDownloadUrlMutation,
} = attachmentsApi;
