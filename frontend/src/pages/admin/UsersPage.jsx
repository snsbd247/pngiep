import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
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
import { formatDateTime } from '@/lib/utils'
import { passwordRule, withPasswordConfirmation } from '@/lib/validation'
import { roleService, userService } from '@/services/admin'

const baseSchema = z.object({
  name: z.string().trim().min(1, 'Name is required.').max(120),
  email: z.string().trim().email('Enter a valid email address.'),
  phone: z.string().max(30).optional().or(z.literal('')),
  role_id: z.coerce.number({ message: 'Select a role.' }).min(1, 'Select a role.'),
  is_active: z.boolean(),
  password_confirmation: z.string().optional(),
})
const createSchema = withPasswordConfirmation(baseSchema.extend({ password: passwordRule }))
const editSchema = withPasswordConfirmation(baseSchema.extend({ password: passwordRule.optional().or(z.literal('')) }))

function UserForm({ user, open, onClose }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { user: me } = useAuth()
  const isSelf = user && me?.id === user.id
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
      })
    }
  }, [open, user, reset])

  const onSubmit = async (values) => {
    try {
      const res = user ? await userService.update(user.id, values) : await userService.create(values)
      toast.success(res.message)
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
        <FormInput label="Email" type="email" required {...register('email')} error={errors.email?.message} />
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

export default function UsersPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { user: me } = useAuth()
  const [params, setParams] = useListParams()
  // ?new=1 (Users & Roles › Add User) opens the form straight away.
  const [editing, setEditing] = useState(() => (new URLSearchParams(window.location.search).get('new') === '1' ? null : undefined))
  const [deleting, setDeleting] = useState(null)

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
    { key: 'is_active', header: 'Status', render: (u) => <Badge status={u.is_active ? 'active' : 'inactive'} /> },
    { key: 'last_login_at', header: 'Last sign-in', render: (u) => (u.last_login_at ? formatDateTime(u.last_login_at) : 'Never') },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (u) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="icon" onClick={() => setEditing(u)} aria-label={`Edit ${u.name}`}><Pencil className="size-4" /></Button>
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
      <UserForm open={editing !== undefined} user={editing} onClose={() => setEditing(undefined)} />
      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title={`Delete ${deleting?.name}?`}
        message="They will no longer be able to sign in."
      />
    </>
  )
}
