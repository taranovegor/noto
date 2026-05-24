import type { AttachmentResponseDto } from '../attachments';

export interface NotebookResponseDto {
  id: string;
  title: string;
  description: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateNotebookDto {
  title: string;
  description: string;
}

export interface UpdateNotebookDto {
  title?: string | null;
  description?: string | null;
}

export interface NoteResponseDto {
  id: string;
  notebookId: string;
  title: string;
  content: string;
  createdAt: string;
  updatedAt: string;
  attachments?: AttachmentResponseDto[];
}

export interface CreateNoteDto {
  title: string;
  content: string;
  attachments?: string[];
}

export interface UpdateNoteDto {
  title?: string | null;
  content?: string | null;
}
