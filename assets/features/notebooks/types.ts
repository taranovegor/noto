import type { AttachmentResponseDto } from '../attachments';

export interface NotebookResponseDto {
  id: string;
  title: string;
  description: string;
  extractionInstructions: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateNotebookDto {
  title: string;
  description: string;
  extractionInstructions?: string | null;
}

export interface UpdateNotebookDto {
  title?: string | null;
  description?: string | null;
  extractionInstructions?: string | null;
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

export interface ExtractionResponseDto {
  id: string;
  status: 'pending' | 'processing' | 'done' | 'failed';
  targetType: 'note';
  errorMessage: string | null;
  createdAt: string;
  updatedAt: string;
  targetParentId: string | null;
  prompt: string | null;
  sources?: AttachmentResponseDto[] | null;
}

export interface CreateExtractionDto {
  attachments: string[];
  targetType: 'note';
  targetParent: string;
  prompt?: string | null;
}
