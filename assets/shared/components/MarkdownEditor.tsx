import React, { useEffect, useRef } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import Document from '@tiptap/extension-document';
import { Placeholder } from '@tiptap/extension-placeholder';
import type { Level } from '@tiptap/extension-heading';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from 'tiptap-markdown';
import { InternalLinkPaste } from './InternalLinkPaste';

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
      ...(enforceFirstLineHeading
        ? [
            Placeholder.configure({
              placeholder: ({ node }) =>
                node.type.name === 'heading' ? 'Memo' : (placeholder ?? ''),
            }),
          ]
        : []),
      Markdown,
      InternalLinkPaste,
    ],
    content: value,
    onUpdate({ editor }) {
      const storage = editor.storage as unknown as Record<string, { getMarkdown: () => string }>;
      const markdown = storage.markdown.getMarkdown();
      lastInternalValue.current = markdown;
      onChange(markdown);
    },
    editorProps: {
      attributes: {
        class: 'tiptap-editor',
        ...(enforceFirstLineHeading ? {} : { 'data-placeholder': placeholder ?? '' }),
      },
    },
  });

  useEffect(() => {
    if (editor && value !== lastInternalValue.current) {
      lastInternalValue.current = value;
      editor.commands.setContent(value);
    }
  }, [editor, value]);

  return <EditorContent editor={editor} />;
}
