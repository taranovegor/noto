export interface AttachmentResponseDto {
  id: string;
  originFilename: string;
  mimeType: string;
  size: number;
  status: 'pending' | 'uploaded';
  createdAt: string;
}

export interface AttachmentUploadResponseDto extends AttachmentResponseDto {
  uploadUrl: string;
}

export interface AttachmentDownloadResponseDto extends AttachmentResponseDto {
  downloadUrl: string;
}

export interface AttachmentDto {
  originFilename: string;
  mimeType: string;
  size: number;
}
