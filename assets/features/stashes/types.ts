import type { AttachmentResponseDto } from '../attachments';

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

export interface CreateStashDto {
  type: StashType;
  content?: string | null;
  attachments?: string[] | null;
}

export interface UpdateStashDto {
  pinned?: boolean | null;
}
