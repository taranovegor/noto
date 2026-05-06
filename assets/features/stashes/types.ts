import type {
  AttachmentResponseDto,
  AttachmentUploadResponseDto,
  AttachmentDto,
} from '../attachments';

export type StashType = 'text' | 'file';

export interface StashResponseDto {
  id: string;
  type: StashType;
  content: string | null;
  createdAt: string;
  expiresAt: string | null;
  pinned: boolean;
  attachments: AttachmentResponseDto[] | null;
}

export interface CreateStashResponseDto extends Omit<StashResponseDto, 'attachments'> {
  attachments: AttachmentUploadResponseDto[] | null;
}

export interface CreateStashDto {
  type: StashType;
  content?: string | null;
  attachments?: AttachmentDto[] | null;
}

export interface UpdateStashDto {
  pinned?: boolean | null;
}
