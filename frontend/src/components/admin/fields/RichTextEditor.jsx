import Image from '@tiptap/extension-image'
import { EditorContent, useEditor, useEditorState } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import { Bold, Heading2, Heading3, ImagePlus, Italic, Link2, List, ListOrdered, Quote, Redo2, RemoveFormatting, Undo2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import MediaPickerModal from '@/components/admin/MediaPickerModal'
import { cn } from '@/lib/utils'

function ToolButton({ active, onClick, label, children, disabled }) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      aria-pressed={active}
      disabled={disabled}
      onMouseDown={(e) => e.preventDefault()}
      onClick={onClick}
      className={cn('grid size-8 place-items-center rounded-md transition disabled:opacity-40', active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100')}
    >
      {children}
    </button>
  )
}

/**
 * WYSIWYG editor (TipTap) that reads/writes HTML. The API purifies the HTML on save,
 * so only safe markup (headings, lists, links, images, quotes) is ever stored.
 */
export default function RichTextEditor({ value, onChange, placeholder, invalid, minHeight = 220 }) {
  const [mediaOpen, setMediaOpen] = useState(false)

  const editor = useEditor({
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3, 4] }, link: { openOnClick: false, autolink: true, protocols: ['http', 'https', 'mailto', 'tel'] } }),
      Image,
    ],
    content: value || '',
    editorProps: {
      attributes: { class: 'prose-content max-w-none px-4 py-3 focus:outline-none', style: `min-height:${minHeight}px`, 'aria-label': placeholder || 'Content editor' },
    },
    onUpdate: ({ editor: e }) => onChange(e.isEmpty ? '' : e.getHTML()),
  })

  // Keep the editor in sync when the form is reset with new data.
  useEffect(() => {
    if (!editor || editor.isDestroyed || value === undefined) return
    if (value !== editor.getHTML() && !(editor.isEmpty && !value)) {
      editor.commands.setContent(value || '', { emitUpdate: false })
    }
  }, [editor, value])

  const state = useEditorState({
    editor,
    selector: ({ editor: e }) =>
      e && {
        bold: e.isActive('bold'),
        italic: e.isActive('italic'),
        h2: e.isActive('heading', { level: 2 }),
        h3: e.isActive('heading', { level: 3 }),
        bullet: e.isActive('bulletList'),
        ordered: e.isActive('orderedList'),
        quote: e.isActive('blockquote'),
        link: e.isActive('link'),
        canUndo: e.can().undo(),
        canRedo: e.can().redo(),
      },
  })

  if (!editor || !state) return <div className="h-56 animate-pulse rounded-xl bg-slate-100" />

  const setLink = () => {
    const previous = editor.getAttributes('link').href
    const url = window.prompt('Link URL (https://… or /page)', previous || '')
    if (url === null) return
    if (url === '') editor.chain().focus().extendMarkRange('link').unsetLink().run()
    else editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }

  return (
    <div className={cn('overflow-hidden rounded-xl border bg-white focus-within:ring-4', invalid ? 'border-rose-400 focus-within:ring-rose-100' : 'border-slate-200 focus-within:border-primary focus-within:ring-primary-100')}>
      <div className="flex flex-wrap gap-0.5 border-b border-slate-100 bg-slate-50/80 p-1.5">
        <ToolButton label="Bold" active={state.bold} onClick={() => editor.chain().focus().toggleBold().run()}><Bold className="size-4" /></ToolButton>
        <ToolButton label="Italic" active={state.italic} onClick={() => editor.chain().focus().toggleItalic().run()}><Italic className="size-4" /></ToolButton>
        <ToolButton label="Heading" active={state.h2} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}><Heading2 className="size-4" /></ToolButton>
        <ToolButton label="Sub-heading" active={state.h3} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}><Heading3 className="size-4" /></ToolButton>
        <ToolButton label="Bullet list" active={state.bullet} onClick={() => editor.chain().focus().toggleBulletList().run()}><List className="size-4" /></ToolButton>
        <ToolButton label="Numbered list" active={state.ordered} onClick={() => editor.chain().focus().toggleOrderedList().run()}><ListOrdered className="size-4" /></ToolButton>
        <ToolButton label="Quote" active={state.quote} onClick={() => editor.chain().focus().toggleBlockquote().run()}><Quote className="size-4" /></ToolButton>
        <ToolButton label="Link" active={state.link} onClick={setLink}><Link2 className="size-4" /></ToolButton>
        <ToolButton label="Insert image" onClick={() => setMediaOpen(true)}><ImagePlus className="size-4" /></ToolButton>
        <ToolButton label="Clear formatting" onClick={() => editor.chain().focus().unsetAllMarks().clearNodes().run()}><RemoveFormatting className="size-4" /></ToolButton>
        <span className="mx-1 w-px bg-slate-200" />
        <ToolButton label="Undo" disabled={!state.canUndo} onClick={() => editor.chain().focus().undo().run()}><Undo2 className="size-4" /></ToolButton>
        <ToolButton label="Redo" disabled={!state.canRedo} onClick={() => editor.chain().focus().redo().run()}><Redo2 className="size-4" /></ToolButton>
      </div>
      <EditorContent editor={editor} />
      <MediaPickerModal
        open={mediaOpen}
        onClose={() => setMediaOpen(false)}
        folder="content"
        onSelect={(media) => {
          editor.chain().focus().setImage({ src: media.url, alt: media.alt || '' }).run()
          setMediaOpen(false)
        }}
      />
    </div>
  )
}
