import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ShieldCheck } from 'lucide-react'
import { useState } from 'react'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { cn, headline } from '@/lib/utils'
import { roleService } from '@/services/admin'
import { useHashScroll } from '@/hooks/useHashScroll'

const SUPER_ADMIN = 'super-admin'

function RoleEditor({ role, permissionGroups }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const locked = role.slug === SUPER_ADMIN
  // Remounted (via key) whenever the saved permissions change, so this initial state stays in sync.
  const [granted, setGranted] = useState(() => new Set(role.permissions))

  const save = useMutation({
    mutationFn: () => roleService.update(role.id, { permissions: [...granted] }),
    onSuccess: (res) => {
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', 'roles'] })
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
    onError: (e) => toast.error(e.message),
  })

  const toggle = (slug) =>
    setGranted((prev) => {
      const next = new Set(prev)
      if (next.has(slug)) next.delete(slug)
      else next.add(slug)
      return next
    })

  return (
    <Card
      title={role.name}
      description={role.description}
      actions={
        <div className="flex items-center gap-3">
          <Badge tone="neutral">{role.users_count} user(s)</Badge>
          {!locked && <Button size="sm" onClick={() => save.mutate()} loading={save.isPending}>Save</Button>}
        </div>
      }
    >
      {locked ? (
        <p className="flex items-center gap-2 text-sm text-slate-500">
          <ShieldCheck className="size-4 text-emerald-600" /> Super Admin always has every permission.
        </p>
      ) : (
        <div className="grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-3">
          {Object.entries(permissionGroups).map(([module, perms]) => (
            <fieldset key={module}>
              <legend className="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">{headline(module)}</legend>
              <div className="space-y-2">
                {perms.map((p) => (
                  <label key={p.slug} className={cn('flex cursor-pointer items-center gap-2.5 text-sm', granted.has(p.slug) ? 'text-slate-800' : 'text-slate-500')}>
                    <input type="checkbox" className="size-4 rounded accent-primary" checked={granted.has(p.slug)} onChange={() => toggle(p.slug)} />
                    {p.name}
                  </label>
                ))}
              </div>
            </fieldset>
          ))}
        </div>
      )}
    </Card>
  )
}

export default function RolesPage() {
  const query = useQuery({ queryKey: ['admin', 'roles'], queryFn: roleService.list })
  useHashScroll(!!query.data)

  return (
    <>
      <PageHeader title="Roles & Permissions" description="Control what each role can see and change in the admin panel." />
      {query.isPending ? (
        <LoadingSkeleton variant="table" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : (
        <div id="permissions" className="scroll-mt-24 space-y-6">
          {query.data.roles.map((role) => (
            <RoleEditor key={`${role.id}:${role.permissions.join(',')}`} role={role} permissionGroups={query.data.permissions} />
          ))}
        </div>
      )}
    </>
  )
}
