export interface ProjectResponseDto {
  id: string;
  name: string;
  prefix: string;
  aliases: Record<string, unknown> | unknown[];
  createdAt: string;
}
