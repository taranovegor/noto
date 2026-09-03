import React, { useEffect, useRef } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import Document from '@tiptap/extension-document';
import { Placeholder } from '@tiptap/extension-placeholder';
import type { Level } from '@tiptap/extension-heading';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from '@tiptap/markdown';
import { Mathematics } from '@tiptap/extension-mathematics';
import { InternalLinkPaste } from './InternalLinkPaste';
import 'katex/dist/katex.min.css';

interface MarkdownEditorProps {
  value: string;
  onChange: (markdown: string) => void;
  placeholder?: string;
  headingLevels?: number[];
  enforceFirstLineHeading?: boolean;
}

const FirstLineHeadingDocument = Document.extend({
  content: 'heading block*',
});

export function MarkdownEditor({
  value,
  onChange,
  placeholder,
  headingLevels,
  enforceFirstLineHeading,
}: MarkdownEditorProps) {
  const lastInternalValue = useRef(value);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        document: enforceFirstLineHeading ? false : undefined,
        ...(headingLevels ? { heading: { levels: headingLevels as Level[] } } : {}),
        ...(enforceFirstLineHeading ? { trailingNode: { node: 'paragraph' } } : {}),
      }),
      ...(enforceFirstLineHeading ? [FirstLineHeadingDocument] : []),
      Placeholder.configure({
        placeholder: ({ node }) =>
          enforceFirstLineHeading && node.type.name === 'heading' ? 'Memo' : (placeholder ?? ''),
      }),
      Mathematics,
      Markdown,
      InternalLinkPaste,
    ],
    content: value,
    contentType: 'markdown',
    onUpdate({ editor }) {
      const markdown = editor.getMarkdown();
      lastInternalValue.current = markdown;
      onChange(markdown);
    },
    editorProps: {
      attributes: {
        class: 'tiptap-editor',
      },
    },
  });

  useEffect(() => {
    if (editor && value !== lastInternalValue.current) {
      lastInternalValue.current = value;
      editor.commands.setContent(value, { contentType: 'markdown' });
    }
  }, [editor, value]);

  return <EditorContent editor={editor} />;
}
