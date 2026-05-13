export interface NoteResponseDto {
  id: string;
  content: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateNoteDto {
  content: string;
}

export interface UpdateNoteDto {
  content?: string | null;
}
