import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, CheckCircle2, Database, Download, HardDrive, RefreshCw } from 'lucide-react'
import { useState } from 'react'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { formatBytes, formatDateTime } from '@/lib/utils'
import { saveBlob } from '@/pages/admin/crm/shared'
import { systemService } from '@/services/admin'

function Row({ label, value, warn }) {
  return (
    <div className="flex items-start justify-between gap-4 py-2.5 text-sm">
      <dt className="text-slate-500">{label}</dt>
      <dd className={warn ? 'flex items-center gap-1.5 font-medium text-amber-600' : 'text-right font-medium text-slate-800'}>
        {warn && <AlertTriangle className="size-4" />}{value}
      </dd>
    </div>
  )
}

/** Settings › System Settings: health, caches and backups. */
export default function SystemSettingsPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const query = useQuery({ queryKey: ['admin', 'system'], queryFn: systemService.info })
  const backups = useQuery({ queryKey: ['admin', 'system', 'backups'], queryFn: systemService.backups })
  const [downloading, setDownloading] = useState(null)
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'system'] })
  const onError = (e) => toast.error(e.message)
  const clear = useMutation({ mutationFn: systemService.clearCache, onSuccess: (r) => { toast.success(r.message); refresh() }, onError })
  const backup = useMutation({ mutationFn: systemService.backup, onSuccess: (r) => { toast.success(r.message); refresh() }, onError })

  const download = async (name) => {
    setDownloading(name)
    try {
      saveBlob(await systemService.download(name), name)
    } catch {
      toast.error('Download failed.')
    } finally {
      setDownloading(null)
    }
  }

  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  const d = query.data
  const queueStuck = d.queue.oldest_pending_minutes !== null && d.queue.oldest_pending_minutes > 5

  return (
    <>
      <PageHeader title="System Settings" description="Health of the website, caches and backups." />
      <div className="grid gap-6 xl:grid-cols-2">
        <Card title="Application">
          <dl className="divide-y divide-slate-100">
            <Row label="Environment" value={d.app.environment} />
            <Row label="Debug mode" value={d.app.debug ? 'On (turn off on the live site)' : 'Off'} warn={d.app.debug && d.app.environment === 'production'} />
            <Row label="Website address" value={d.app.url} />
            <Row label="PHP / Laravel" value={`${d.app.php} / ${d.app.laravel}`} />
            <Row label="Database" value={d.app.database} />
            <Row label="Configuration cached" value={d.app.config_cached ? 'Yes' : 'No'} />
            <Row label="Demo content left" value={d.demo_content ? `${d.demo_content} items` : 'None'} />
          </dl>
          <Button className="mt-4" variant="outline" onClick={() => clear.mutate()} loading={clear.isPending}><RefreshCw className="size-4" /> Clear caches</Button>
          <p className="mt-2 text-xs text-slate-500">Use after changing files on the server, or if the website shows outdated content.</p>
        </Card>

        <Card title="Email & background jobs">
          <dl className="divide-y divide-slate-100">
            <Row label="Mail sender" value={`${d.mail.mailer}${d.mail.host ? ` · ${d.mail.host}` : ''}`} />
            <Row label="From address" value={d.mail.from} />
            <Row label="Emails waiting to send" value={d.queue.pending} warn={queueStuck} />
            <Row label="Failed jobs" value={d.queue.failed} warn={d.queue.failed > 0} />
          </dl>
          {queueStuck ? (
            <p className="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-800"><AlertTriangle className="size-4 shrink-0" /> Emails have been waiting for {d.queue.oldest_pending_minutes} minutes. Check that the cron job is running (see DEPLOYMENT.md § 7).</p>
          ) : (
            <p className="mt-3 flex items-center gap-2 text-xs text-emerald-700"><CheckCircle2 className="size-4" /> Background jobs are being processed.</p>
          )}
        </Card>

        <Card title="Storage">
          <dl className="divide-y divide-slate-100">
            <Row label="Media library" value={formatBytes(d.storage.media)} />
            <Row label="Application documents" value={formatBytes(d.storage.documents)} />
            <Row label="Backups" value={formatBytes(d.storage.backups)} />
            {d.storage.disk_free !== null && <Row label="Free disk space" value={formatBytes(d.storage.disk_free)} />}
          </dl>
        </Card>

        <Card
          title="Backups"
          description="Automatic: database nightly, database + files weekly. Kept on the server outside the website folder."
          actions={
            <div className="flex gap-2">
              <Button size="sm" variant="outline" onClick={() => backup.mutate(false)} loading={backup.isPending && !backup.variables}><Database className="size-4" /> Backup database</Button>
              <Button size="sm" variant="outline" onClick={() => backup.mutate(true)} loading={backup.isPending && !!backup.variables}><HardDrive className="size-4" /> + files</Button>
            </div>
          }
          bodyClassName="p-0"
        >
          {!backups.data?.length ? <p className="p-5 text-sm text-slate-500">No backups yet.</p> : (
            <ul className="max-h-80 divide-y divide-slate-100 overflow-y-auto">
              {backups.data.map((b) => (
                <li key={b.name} className="flex items-center gap-3 px-5 py-2.5 text-sm">
                  <span className="min-w-0 flex-1">
                    <span className="block truncate font-medium text-slate-800">{b.name}</span>
                    <span className="block text-xs text-slate-500">{b.type === 'database' ? 'Database' : 'Database files + uploads'} · {formatBytes(b.size)} · {formatDateTime(b.created_at)}</span>
                  </span>
                  {d.can_download_backups && (
                    <Button size="sm" variant="ghost" onClick={() => download(b.name)} loading={downloading === b.name} aria-label={`Download ${b.name}`}><Download className="size-4" /></Button>
                  )}
                </li>
              ))}
            </ul>
          )}
          {!d.can_download_backups && <p className="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Only a Super Admin can download backups, because they contain all student data.</p>}
        </Card>
      </div>
    </>
  )
}
