import type { AttachmentResponseDto } from '../attachments';

export interface MemoResponseDto {
  id: string;
  content: string;
  createdAt: string;
  updatedAt: string;
  attachments?: AttachmentResponseDto[];
}

export interface CreateMemoDto {
  content: string;
  attachments?: string[];
}

export interface UpdateMemoDto {
  content?: string | null;
}
