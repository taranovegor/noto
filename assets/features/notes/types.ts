import type { AttachmentResponseDto } from '../attachments';

export interface NoteResponseDto {
  id: string;
  content: string;
  createdAt: string;
  updatedAt: string;
  attachments?: AttachmentResponseDto[];
}

export interface CreateNoteDto {
  content: string;
  attachments?: string[];
}

export interface UpdateNoteDto {
  content?: string | null;
}
