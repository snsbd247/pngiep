import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell, CalendarCheck, CheckCheck, FileText, Mail, Settings, Trash2, Trophy, UserRound } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import FormSwitch from '@/components/forms/FormSwitch'
import Button from '@/components/ui/Button'
import Pagination from '@/components/ui/Pagination'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useListParams } from '@/hooks/useListParams'
import { cn, formatDateTime } from '@/lib/utils'
import { notificationService } from '@/services/admin'

// eslint-disable-next-line react-refresh/only-export-components
export const NOTIFICATION_ICONS = {
  enquiry: { icon: UserRound, cls: 'bg-sky-50 text-sky-600' },
  appointment: { icon: CalendarCheck, cls: 'bg-emerald-50 text-emerald-600' },
  application: { icon: FileText, cls: 'bg-violet-50 text-violet-600' },
  message: { icon: Mail, cls: 'bg-indigo-50 text-indigo-600' },
  story: { icon: Trophy, cls: 'bg-amber-50 text-amber-600' },
}

/** Notifications › All / Unread (?filter=unread). */
export default function NotificationsPage() {
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'notifications', 'list', params], queryFn: () => notificationService.list(params), placeholderData: (p) => p })
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'notifications'] })
  const onError = (e) => toast.error(e.message)
  const readAll = useMutation({ mutationFn: notificationService.markAllRead, onSuccess: (r) => { toast.success(r.message); refresh() }, onError })
  const remove = useMutation({ mutationFn: notificationService.remove, onSuccess: refresh, onError })

  const open = async (n) => {
    if (!n.read) await notificationService.markRead(n.id).catch(() => {})
    refresh()
    if (n.url) navigate(n.url)
  }

  const unreadOnly = params.filter === 'unread'
  const rows = query.data?.data ?? []
  const tab = (active) => cn('rounded-lg px-4 py-2 text-sm font-medium', active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100')

  return (
    <>
      <PageHeader
        title={unreadOnly ? 'Unread Notifications' : 'Notifications'}
        description="New enquiries, bookings, applications and messages that need your attention."
        actions={
          <>
            <Button to="/admin/notifications/settings" variant="outline"><Settings className="size-4" /> Settings</Button>
            <Button onClick={() => readAll.mutate()} loading={readAll.isPending} disabled={!query.data?.meta?.unread}><CheckCheck className="size-4" /> Mark all as read</Button>
          </>
        }
      />
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex gap-1" role="tablist">
          <button type="button" role="tab" aria-selected={!unreadOnly} className={tab(!unreadOnly)} onClick={() => setParams({ filter: '' })}>All</button>
          <button type="button" role="tab" aria-selected={unreadOnly} className={tab(unreadOnly)} onClick={() => setParams({ filter: 'unread' })}>
            Unread {query.data?.meta?.unread ? <span className="ml-1 rounded-full bg-rose-500 px-1.5 text-[11px] text-white">{query.data.meta.unread}</span> : null}
          </button>
        </div>
        <FormSelect className="sm:w-56" aria-label="Type" placeholder="All types" value={params.type ?? ''} onChange={(e) => setParams({ type: e.target.value })} options={query.data?.meta?.types ?? []} />
      </div>

      {query.isPending ? <LoadingSkeleton variant="table" /> : query.isError ? <ErrorState error={query.error} onRetry={query.refetch} /> : !rows.length ? (
        <EmptyState icon={Bell} title={unreadOnly ? "You're all caught up" : 'No notifications yet'} description="New activity from the website will appear here." />
      ) : (
        <>
          <ul className={cn('divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card', query.isFetching && 'opacity-70')}>
            {rows.map((n) => {
              const meta = NOTIFICATION_ICONS[n.type] ?? { icon: Bell, cls: 'bg-slate-100 text-slate-600' }
              return (
                <li key={n.id} className={cn('flex items-center gap-4 px-4 py-3 sm:px-5', !n.read && 'bg-primary-50/40')}>
                  <span className={cn('grid size-10 shrink-0 place-items-center rounded-xl', meta.cls)}><meta.icon className="size-5" /></span>
                  <button type="button" onClick={() => open(n)} className="min-w-0 flex-1 text-left">
                    <span className={cn('block truncate text-sm', n.read ? 'text-slate-700' : 'font-semibold text-slate-900')}>{n.title}</span>
                    {n.body && <span className="block truncate text-xs text-slate-500">{n.body}</span>}
                  </button>
                  <span className="hidden text-xs whitespace-nowrap text-slate-500 sm:block">{formatDateTime(n.created_at)}</span>
                  {!n.read && <span className="size-2 shrink-0 rounded-full bg-primary" aria-label="Unread" />}
                  <Button variant="ghost" size="icon" className="text-slate-400 hover:text-rose-600" onClick={() => remove.mutate(n.id)} aria-label="Delete notification"><Trash2 className="size-4" /></Button>
                </li>
              )
            })}
          </ul>
          <Pagination className="mt-5" meta={query.data?.meta} onPageChange={(page) => setParams({ page })} />
        </>
      )}
    </>
  )
}

/** Notifications › Notification Settings: which in-app notifications this user receives. */
export function NotificationSettingsPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { can } = useAuth()
  const query = useQuery({ queryKey: ['admin', 'notifications', 'preferences'], queryFn: notificationService.preferences })
  const save = useMutation({
    mutationFn: (prefs) => notificationService.savePreferences(prefs),
    onSuccess: (r) => { toast.success(r.message); queryClient.invalidateQueries({ queryKey: ['admin', 'notifications', 'preferences'] }) },
    onError: (e) => toast.error(e.message),
  })

  return (
    <>
      <PageHeader title="Notification Settings" description="Choose what appears in your notification bell. These settings are personal." actions={<Button to="/admin/notifications" variant="outline">All notifications</Button>} />
      <div className="max-w-2xl space-y-6">
        <Card title="In-app notifications">
          {query.isPending ? <LoadingSkeleton count={3} /> : query.isError ? <ErrorState error={query.error} onRetry={query.refetch} /> : !query.data.length ? (
            <p className="text-sm text-slate-500">Your role does not receive notifications.</p>
          ) : (
            <ul className="divide-y divide-slate-100">
              {query.data.map((p) => (
                <li key={p.type} className="py-3 first:pt-0 last:pb-0">
                  <FormSwitch label={p.label} checked={p.enabled} onChange={(enabled) => save.mutate({ [p.type]: enabled })} />
                </li>
              ))}
            </ul>
          )}
        </Card>
        {can('settings.manage') && (
          <Card title="Email notifications" description="New enquiries, appointments and applications are also emailed to the office.">
            <p className="text-sm text-slate-600">
              Set the receiving address under{' '}
              <Link to="/admin/settings/email#setting-admin_notification_email" className="font-medium text-primary hover:underline">Settings › Email › Admin notification email</Link>,
              and edit the wording in <Link to="/admin/content/email-templates" className="font-medium text-primary hover:underline">Email Templates</Link>.
            </p>
          </Card>
        )}
      </div>
    </>
  )
}
