import { closestCenter, DndContext, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core'
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { GripVertical } from 'lucide-react'
import { cn } from '@/lib/utils'

function SortableRow({ id, disabled, children, className }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id, disabled })
  return (
    <li ref={setNodeRef} style={{ transform: CSS.Transform.toString(transform), transition }} className={cn('relative', isDragging && 'z-10 shadow-card-hover', className)}>
      <div className="flex items-center gap-2">
        {!disabled && (
          <button type="button" {...attributes} {...listeners} className="grid size-8 shrink-0 cursor-grab touch-none place-items-center rounded-md text-slate-400 hover:bg-slate-100 active:cursor-grabbing" aria-label="Drag to reorder">
            <GripVertical className="size-4" />
          </button>
        )}
        <div className="min-w-0 flex-1">{children}</div>
      </div>
    </li>
  )
}

/**
 * Drag & drop (mouse, touch and keyboard) vertical list.
 * onReorder receives the new array; items must have a unique `id`.
 */
export default function SortableList({ items, onReorder, renderItem, disabled = false, className, itemClassName }) {
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }), useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }))

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragEnd={({ active, over }) => {
        if (!over || active.id === over.id) return
        const from = items.findIndex((i) => i.id === active.id)
        const to = items.findIndex((i) => i.id === over.id)
        onReorder(arrayMove(items, from, to))
      }}
    >
      <SortableContext items={items.map((i) => i.id)} strategy={verticalListSortingStrategy}>
        <ul className={className}>
          {items.map((item, i) => (
            <SortableRow key={item.id} id={item.id} disabled={disabled} className={itemClassName}>
              {renderItem(item, i)}
            </SortableRow>
          ))}
        </ul>
      </SortableContext>
    </DndContext>
  )
}
