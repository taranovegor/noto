import React, { useEffect, useRef } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from 'tiptap-markdown';

interface MarkdownEditorProps {
  value: string;
  onChange: (markdown: string) => void;
  placeholder?: string;
}

export function MarkdownEditor({ value, onChange, placeholder }: MarkdownEditorProps) {
  const lastInternalValue = useRef(value);

  const editor = useEditor({
    extensions: [StarterKit, Markdown],
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
        'data-placeholder': placeholder ?? '',
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
