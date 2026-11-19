import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Send } from 'lucide-react'
import Badge from '@/components/ui/Badge'
import { useToast } from '@/components/ui/Toast'
import { facebookService } from '@/services/admin'

/**
 * Facebook column for News / Blog lists: posted, waiting, failed (with reason) or off,
 * plus "Post now" (older or opted-out entries) / "Retry" (after an error).
 */
export default function FacebookCell({ resource, row }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const post = useMutation({
    mutationFn: () => facebookService.postNow(resource, row.id),
    onSuccess: (res) => {
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', resource] })
    },
    onError: (e) => toast.error(e.message),
  })

  if (row.facebook_post_id) {
    return <Badge tone="success">Posted</Badge>
  }

  const button = (label) => (
    <button
      type="button"
      onClick={() => post.mutate()}
      disabled={post.isPending}
      className="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline disabled:opacity-50"
    >
      <Send className="size-3.5" /> {label}
    </button>
  )

  if (row.facebook_error) {
    return (
      <div className="flex flex-col items-start gap-1">
        <span title={row.facebook_error}><Badge tone="danger">Failed</Badge></span>
        {button('Retry')}
      </div>
    )
  }

  if (row.facebook_post) {
    // Published (now or on its scheduled date) → waiting for the queue; draft → posted once published.
    return row.status === 'published' ? <Badge tone="warning">Waiting</Badge> : <Badge tone="neutral">On publish</Badge>
  }

  return button('Post now')
}
