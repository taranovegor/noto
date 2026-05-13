import { marked } from 'marked';

interface MarkdownToken {
  type: string;
  text?: string;
  tokens?: MarkdownToken[];
  items?: MarkdownToken[];
}

function collectText(tokens: MarkdownToken[]): string[] {
  const parts: string[] = [];
  for (const token of tokens) {
    switch (token.type) {
      case 'text':
      case 'codespan':
      case 'escape':
        parts.push(token.text ?? '');
        break;
      default:
        if (token.tokens) {
          parts.push(...collectText(token.tokens));
        }
        if (token.type === 'list' && Array.isArray(token.items)) {
          for (const item of token.items) {
            if (item.tokens) {
              parts.push(...collectText(item.tokens));
            }
          }
        }
    }
  }
  return parts;
}

export function renderPlainText(markdown: string): string {
  return collectText(marked.lexer(markdown) as MarkdownToken[])
    .join(' ')
    .replace(/\s+/g, ' ')
    .trim();
}
