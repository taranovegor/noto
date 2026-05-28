import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { parseInternalUrl, resolveInternalLink } from '../utils';

export const InternalLinkPaste = Extension.create({
  name: 'internalLinkPaste',

  addProseMirrorPlugins() {
    const editor = this.editor;

    return [
      new Plugin({
        key: new PluginKey('internalLinkPaste'),
        props: {
          handlePaste(view, event) {
            const text = event.clipboardData?.getData('text/plain')?.trim();
            if (!text) return false;

            const parsed = parseInternalUrl(text);
            if (!parsed) return false;

            // Prevent ProseMirror from inserting the raw URL.
            // We'll insert the formatted link once the title is resolved.
            const { from, to } = view.state.selection;

            resolveInternalLink(parsed.uuid, parsed.type).then(({ title }) => {
              if (editor.isDestroyed) return;

              editor
                .chain()
                .focus()
                .setTextSelection({ from, to })
                .insertContent({
                  type: 'text',
                  text: title,
                  marks: [
                    {
                      type: 'link',
                      attrs: { href: `/refs/${parsed.uuid}` },
                    },
                  ],
                })
                .run();
            });

            return true;
          },
        },
      }),
    ];
  },
});
