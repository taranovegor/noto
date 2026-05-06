import type { LucideIcon } from 'lucide-react';
import { File, FileText, Code2, Image, Music, Video, Archive, Table } from 'lucide-react';

const MIME_TYPE_ICONS: Record<string, LucideIcon> = {
  'text/plain': FileText,
  'text/markdown': FileText,
  'application/pdf': FileText,
  'image/jpeg': Image,
  'image/png': Image,
  'image/webp': Image,
  'image/gif': Image,
  'audio/mpeg': Music,
  'audio/wav': Music,
  'video/mp4': Video,
  'video/webm': Video,
  'application/zip': Archive,
  'application/x-rar-compressed': Archive,
  'application/json': Code2,
  'application/vnd.ms-excel': Table,
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': Table,
};

export const getMimeTypeIcon = (mimeType: string): LucideIcon => {
  if (MIME_TYPE_ICONS[mimeType]) {
    return MIME_TYPE_ICONS[mimeType];
  }

  const [mainType] = mimeType.split('/');
  switch (mainType) {
    case 'image':
      return Image;
    case 'audio':
      return Music;
    case 'video':
      return Video;
    case 'text':
      return FileText;
    case 'application':
      return Archive;
    default:
      return File;
  }
};
