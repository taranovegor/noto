import { tokenStorage } from './tokenStorage';

interface EntityDescriptor {
  path: string;
  fallback: string;
  extractTitle: (data: Record<string, unknown>) => string;
}

function stripMarkdown(text: string): string {
  return text
    .replace(/^#{1,6}\s*/, '') // heading markers
    .replace(/\*{1,2}(.+?)\*{1,2}/g, '$1') // bold/italic
    .replace(/_{1,2}(.+?)_{1,2}/g, '$1')
    .replace(/`(.+?)`/g, '$1') // inline code
    .replace(/\[(.+?)]\(.+?\)/g, '$1') // links
    .replace(/^[>|]\s*/, '') // blockquote markers
    .trim();
}

const ENTITY_REGISTRY: Record<string, EntityDescriptor> = {
  notes: {
    path: 'notes',
    fallback: 'Note',
    extractTitle: (data) => {
      const content = String(data.content ?? '');
      return stripMarkdown(content.split('\n')[0]!) || 'Note';
    },
  },
  tasks: {
    path: 'tasks',
    fallback: 'Task',
    extractTitle: (data) => String(data.name || 'Task'),
  },
};

const ENTITY_PATHS = Object.keys(ENTITY_REGISTRY).join('|');

/** Matches internal entity URLs: /notes/<uuid> or /tasks/<uuid> */
const ENTITY_PATH_RE = new RegExp(
  `^/(${ENTITY_PATHS})/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$`,
);

export interface ParsedInternalUrl {
  uuid: string;
  type: string;
}

export function parseInternalUrl(text: string): ParsedInternalUrl | null {
  let url: URL;
  try {
    url = new URL(text);
  } catch {
    try {
      url = new URL(text, window.location.origin);
    } catch {
      return null;
    }
  }

  if (url.hostname && url.hostname !== window.location.hostname) return null;

  const match = url.pathname.match(ENTITY_PATH_RE);
  if (!match) return null;

  return { type: match[1]!, uuid: match[2]! };
}

export interface ResolvedTitle {
  title: string;
}

export async function resolveInternalLink(uuid: string, type: string): Promise<ResolvedTitle> {
  const descriptor = ENTITY_REGISTRY[type];
  if (!descriptor) return { title: `${type}/${uuid}` };

  const token = tokenStorage.getAccessToken();
  const headers: Record<string, string> = {};
  if (token) headers['Authorization'] = `Bearer ${token}`;

  try {
    const res = await fetch(`/api/${descriptor.path}/${uuid}`, { headers });
    if (!res.ok) return { title: descriptor.fallback };
    const data: Record<string, unknown> = await res.json();
    const title = descriptor.extractTitle(data);
    return { title: title || descriptor.fallback };
  } catch {
    return { title: descriptor.fallback };
  }
}
