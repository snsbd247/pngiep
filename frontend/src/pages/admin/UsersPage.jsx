import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, CircleCheck, Copy, Info, Link2, Loader2, Mail, MailX, Pencil, Plus, Trash2, TriangleAlert } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Controller, useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import FormSwitch from '@/components/forms/FormSwitch'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import Modal from '@/components/ui/Modal'
import SearchBar from '@/components/ui/SearchBar'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { useListParams } from '@/hooks/useListParams'
import { cn, formatDateTime } from '@/lib/utils'
import { passwordRule, withPasswordConfirmation } from '@/lib/validation'
import { mailboxService, roleService, userService } from '@/services/admin'

const baseSchema = z.object({
  name: z.string().trim().min(1, 'Name is required.').max(120),
  email: z.string().trim().email('Enter a valid email address.'),
  phone: z.string().max(30).optional().or(z.literal('')),
  role_id: z.coerce.number({ message: 'Select a role.' }).min(1, 'Select a role.'),
  is_active: z.boolean(),
  password_confirmation: z.string().optional(),
  mailbox: z.boolean().optional(),
})
const createSchema = withPasswordConfirmation(baseSchema.extend({ password: passwordRule }))
const editSchema = withPasswordConfirmation(baseSchema.extend({ password: passwordRule.optional().or(z.literal('')) }))

const MAILBOX_HINTS = {
  available: { icon: CircleCheck, cls: 'text-emerald-600', text: (e) => `New mailbox ${e} will be created.` },
  exists: { icon: Link2, cls: 'text-sky-600', text: (e) => `Mailbox ${e} already exists and will be linked.` },
  // Linking existing mail is Super Admin only (it gives password-less access to that mail).
  exists_locked: { icon: Info, cls: 'text-amber-700', text: (e) => `Mailbox ${e} already exists. Only a Super Admin can link an existing mailbox.` },
  linked: { icon: Link2, cls: 'text-sky-600', text: (e) => `Linked to mailbox ${e}.` },
  external: { icon: Info, cls: 'text-slate-500', text: (_, d) => `Not a @${d} address: no mailbox is set up.` },
}

/** Live "what happens to the mailbox" hint under the email field (debounced cPanel check). */
function useMailboxStatus(email, enabled) {
  const [debounced, setDebounced] = useState(email)
  useEffect(() => {
    const t = setTimeout(() => setDebounced(email), 500)
    return () => clearTimeout(t)
  }, [email])
  const valid = /^\S+@\S+\.\S+$/.test(debounced ?? '')
  return useQuery({
    queryKey: ['admin', 'mailbox-status', debounced],
    queryFn: () => mailboxService.status(debounced),
    enabled: enabled && valid,
    staleTime: 30_000,
    retry: false,
  })
}

function UserForm({ user, open, onClose, onMailboxPassword }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { user: me, can, setUser } = useAuth()
  const isSelf = user && me?.id === user.id
  const canMailbox = can('mailboxes.manage')
  const roles = useQuery({ queryKey: ['admin', 'roles', 'options'], queryFn: roleService.options, enabled: open })

  const {
    register,
    control,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(user ? editSchema : createSchema) })
  const handleApiError = useApiErrorHandler(setError)

  const email = (useWatch({ control, name: 'email' }) ?? '').trim().toLowerCase()
  const status = useMailboxStatus(email, open && canMailbox)
  const linkedHere = user?.mailbox && user.mailbox === email
  const rawStatus = linkedHere ? 'linked' : status.data?.status
  const mailboxStatus = rawStatus === 'exists' && !me?.is_super_admin ? 'exists_locked' : rawStatus
  const mailboxPossible = ['available', 'exists', 'linked'].includes(mailboxStatus)
  const hint = MAILBOX_HINTS[mailboxStatus]

  useEffect(() => {
    if (open) {
      reset({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        role_id: user?.role?.id ?? '',
        is_active: user?.is_active ?? true,
        password: '',
        password_confirmation: '',
        mailbox: user ? !!user.mailbox : true,
      })
    }
  }, [open, user, reset])

  const onSubmit = async (values) => {
    // Only send the mailbox choice when it applies; otherwise the server leaves the link alone.
    const payload = { ...values }
    if (!canMailbox || !(mailboxPossible || user?.mailbox)) delete payload.mailbox
    try {
      const res = user ? await userService.update(user.id, payload) : await userService.create(payload)
      const mailbox = res.meta?.mailbox
      if (mailbox?.status === 'error') toast.error(res.message)
      else toast.success(res.message)
      if (mailbox?.password) onMailboxPassword({ email: mailbox.mailbox, password: mailbox.password })
      if (isSelf && res.data) setUser({ ...me, ...res.data })
      queryClient.invalidateQueries({ queryKey: ['admin', 'users'] })
      onClose()
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={user ? 'Edit user' : 'New user'}
      footer={
        <>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="user-form" loading={isSubmitting}>{user ? 'Save changes' : 'Create user'}</Button>
        </>
      }
    >
      <form id="user-form" onSubmit={handleSubmit(onSubmit)} className="grid gap-4 sm:grid-cols-2" noValidate>
        <FormInput label="Name" required {...register('name')} error={errors.name?.message} />
        <div>
          <FormInput label="Email" type="email" required {...register('email')} error={errors.email?.message} />
          {canMailbox && !errors.email && (
            <p className="mt-1.5 flex items-start gap-1.5 text-xs" aria-live="polite">
              {status.isFetching && !linkedHere ? (
                <><Loader2 className="mt-px size-3.5 shrink-0 animate-spin text-slate-400" /> <span className="text-slate-500">Checking mailbox…</span></>
              ) : status.isError && !linkedHere ? (
                <><TriangleAlert className="mt-px size-3.5 shrink-0 text-amber-600" /> <span className="text-amber-700">Mail server check failed: {status.error.message}</span></>
              ) : hint ? (
                <><hint.icon className={cn('mt-px size-3.5 shrink-0', hint.cls)} /> <span className={hint.cls}>{hint.text(email, status.data?.domain)}</span></>
              ) : null}
            </p>
          )}
        </div>
        <FormInput label="Phone" {...register('phone')} error={errors.phone?.message} />
        <FormSelect
          label="Role"
          required
          disabled={isSelf}
          placeholder="Select a role"
          options={(roles.data ?? []).map((r) => ({ value: r.id, label: r.name }))}
          {...register('role_id')}
          error={errors.role_id?.message}
        />
        <FormInput label={user ? 'New password' : 'Password'} type="password" autoComplete="new-password" required={!user} help={user ? 'Leave blank to keep the current password.' : undefined} {...register('password')} error={errors.password?.message} />
        <FormInput label="Confirm password" type="password" autoComplete="new-password" {...register('password_confirmation')} error={errors.password_confirmation?.message} />
        {canMailbox && (mailboxPossible || user?.mailbox) && (
          <Controller
            name="mailbox"
            control={control}
            render={({ field }) => (
              <FormSwitch
                className="sm:col-span-2"
                label="Staff mailbox & Webmail"
                description={mailboxStatus === 'available'
                  ? 'Create this mailbox on the mail server. Its password is shown once after saving.'
                  : 'Link this user to the mailbox so they can open Webmail from the admin panel. Turning it off only unlinks; the mailbox and its mail stay.'}
                checked={!!field.value}
                onChange={field.onChange}
              />
            )}
          />
        )}
        {!isSelf && (
          <Controller
            name="is_active"
            control={control}
            render={({ field }) => (
              <FormSwitch className="sm:col-span-2" label="Active" description="Inactive users cannot sign in." checked={field.value} onChange={field.onChange} />
            )}
          />
        )}
      </form>
    </Modal>
  )
}

/** Shows a new mailbox's generated password once, with a copy button. */
function MailboxPasswordModal({ value, onClose }) {
  const [copied, setCopied] = useState(false)
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(value.password)
      setCopied(true)
    } catch {
      setCopied(false)
    }
  }
  return (
    <Modal open={!!value} onClose={onClose} title="Mailbox created" footer={<Button onClick={onClose}>Done</Button>}>
      {value && (
        <div className="space-y-4 text-sm">
          <p className="text-slate-600">
            Mailbox <strong className="text-slate-800">{value.email}</strong> is ready. The user can open it with the <strong>Webmail</strong> button in the admin panel.
            This password is only needed for phone or Outlook setup.
          </p>
          <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <code className="min-w-0 flex-1 font-mono text-[13px] break-all text-slate-800">{value.password}</code>
            <Button variant="outline" size="sm" onClick={copy}>{copied ? <Check className="size-4" /> : <Copy className="size-4" />} {copied ? 'Copied' : 'Copy'}</Button>
          </div>
          <p className="flex items-start gap-1.5 text-xs text-amber-700"><TriangleAlert className="mt-px size-3.5 shrink-0" /> It is not stored and will not be shown again. Share it with the user securely.</p>
        </div>
      )}
    </Modal>
  )
}

export default function UsersPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { user: me, can } = useAuth()
  const [params, setParams] = useListParams()
  // ?new=1 (Users & Roles › Add User) opens the form straight away.
  const [editing, setEditing] = useState(() => (new URLSearchParams(window.location.search).get('new') === '1' ? null : undefined))
  const [deleting, setDeleting] = useState(null)
  const [newMailbox, setNewMailbox] = useState(null)
  const [removingMailbox, setRemovingMailbox] = useState(null)

  const removeMailbox = useMutation({
    mutationFn: () => mailboxService.unlink(removingMailbox.id, true),
    onSuccess: (res) => {
      toast.success(res.message)
      setRemovingMailbox(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'users'] })
    },
    onError: (e) => toast.error(e.message),
  })

  const query = useQuery({ queryKey: ['admin', 'users', params], queryFn: () => userService.list(params), placeholderData: (p) => p })

  const remove = useMutation({
    mutationFn: () => userService.remove(deleting.id),
    onSuccess: (res) => {
      toast.success(res.message)
      setDeleting(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'users'] })
    },
    onError: (e) => toast.error(e.message),
  })

  const columns = [
    {
      key: 'name',
      header: 'User',
      render: (u) => (
        <div className="text-left">
          <p className="font-semibold text-slate-800">{u.name}</p>
          <p className="text-xs text-slate-500">{u.email}</p>
        </div>
      ),
    },
    { key: 'role', header: 'Role', render: (u) => <Badge tone="primary">{u.role?.name ?? '—'}</Badge> },
    {
      key: 'mailbox',
      header: 'Mailbox',
      render: (u) => (u.mailbox ? <span className="inline-flex items-center gap-1 text-xs text-slate-600"><Mail className="size-3.5 text-primary" /> Linked</span> : <span className="text-xs text-slate-400">—</span>),
    },
    { key: 'is_active', header: 'Status', render: (u) => <Badge status={u.is_active ? 'active' : 'inactive'} /> },
    { key: 'last_login_at', header: 'Last sign-in', render: (u) => (u.last_login_at ? formatDateTime(u.last_login_at) : 'Never') },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (u) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="icon" onClick={() => setEditing(u)} aria-label={`Edit ${u.name}`}><Pencil className="size-4" /></Button>
          {u.mailbox && can('mailboxes.manage') && (
            <Button variant="ghost" size="icon" className="text-amber-600 hover:bg-amber-50" onClick={() => setRemovingMailbox(u)} aria-label={`Delete mailbox of ${u.name}`} title="Delete mailbox from the mail server"><MailX className="size-4" /></Button>
          )}
          {u.id !== me?.id && (
            <Button variant="ghost" size="icon" className="text-rose-600 hover:bg-rose-50" onClick={() => setDeleting(u)} aria-label={`Delete ${u.name}`}><Trash2 className="size-4" /></Button>
          )}
        </div>
      ),
    },
  ]

  return (
    <>
      <PageHeader
        title="Users"
        description="Admin panel accounts and their roles."
        actions={<Button onClick={() => setEditing(null)}><Plus className="size-4" /> New user</Button>}
      />
      <SearchBar className="mb-5 max-w-md" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search by name or email…" />
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} />
      <UserForm open={editing !== undefined} user={editing} onClose={() => setEditing(undefined)} onMailboxPassword={setNewMailbox} />
      <MailboxPasswordModal value={newMailbox} onClose={() => setNewMailbox(null)} />
      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title={`Delete ${deleting?.name}?`}
        message={deleting?.mailbox
          ? `They will no longer be able to sign in. Their mailbox ${deleting.mailbox} is kept on the mail server with all its mail.`
          : 'They will no longer be able to sign in.'}
      />
      <ConfirmDialog
        open={!!removingMailbox}
        onClose={() => setRemovingMailbox(null)}
        onConfirm={() => removeMailbox.mutate()}
        loading={removeMailbox.isPending}
        title={`Delete mailbox ${removingMailbox?.mailbox}?`}
        message="The mailbox and ALL its emails are permanently deleted from the mail server. This cannot be undone."
      />
    </>
  )
}
