import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery } from '@tanstack/react-query'
import { LogIn, LogOut, KeyRound } from 'lucide-react'
import { Controller, useForm } from 'react-hook-form'
import { z } from 'zod'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormInput from '@/components/forms/FormInput'
import ImageField from '@/components/forms/ImageField'
import Button from '@/components/ui/Button'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { useHashScroll } from '@/hooks/useHashScroll'
import { formatDateTime } from '@/lib/utils'
import { profileService } from '@/services/admin'
import { passwordRule, withPasswordConfirmation } from '@/lib/validation'
import { authService } from '@/services/authService'

const schema = withPasswordConfirmation(
  z
    .object({
      name: z.string().trim().min(1, 'Name is required.').max(120),
      email: z.string().trim().email('Enter a valid email address.'),
      phone: z.string().max(30).optional().or(z.literal('')),
      avatar: z.string().nullable().optional(),
      current_password: z.string().optional(),
      password: passwordRule.optional().or(z.literal('')),
      password_confirmation: z.string().optional(),
    })
    .refine((v) => !v.password || v.current_password, { path: ['current_password'], message: 'Enter your current password to set a new one.' }),
)

export default function ProfilePage() {
  const { user, setUser } = useAuth()
  const toast = useToast()
  const {
    register,
    control,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: user.name, email: user.email, phone: user.phone ?? '', avatar: user.avatar, current_password: '', password: '', password_confirmation: '' },
  })
  const handleApiError = useApiErrorHandler(setError)

  const onSubmit = async (values) => {
    try {
      const res = await authService.updateProfile(values)
      setUser(res.data)
      reset({ ...values, current_password: '', password: '', password_confirmation: '' })
      toast.success(res.message)
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <>
      <PageHeader title="My Profile" description="Update your details and password." />
      <form onSubmit={handleSubmit(onSubmit)} className="max-w-3xl space-y-6" noValidate>
        <Card id="profile" title="Profile">
          <div className="grid gap-5 sm:grid-cols-2">
            <Controller
              name="avatar"
              control={control}
              render={({ field }) => (
                <ImageField className="sm:col-span-2" label="Photo" value={field.value} previewUrl={user.avatar === field.value ? user.avatar_url : undefined} folder="avatars" onChange={(path) => field.onChange(path)} />
              )}
            />
            <FormInput label="Name" required {...register('name')} error={errors.name?.message} />
            <FormInput label="Email" type="email" required {...register('email')} error={errors.email?.message} />
            <FormInput label="Phone" {...register('phone')} error={errors.phone?.message} />
          </div>
        </Card>
        <Card id="password" title="Change password" description="Leave blank to keep your current password.">
          <div className="grid gap-5 sm:grid-cols-2">
            <FormInput className="sm:col-span-2" label="Current password" type="password" autoComplete="current-password" {...register('current_password')} error={errors.current_password?.message} />
            <FormInput label="New password" type="password" autoComplete="new-password" {...register('password')} error={errors.password?.message} />
            <FormInput label="Confirm new password" type="password" autoComplete="new-password" {...register('password_confirmation')} error={errors.password_confirmation?.message} />
          </div>
        </Card>
        <Button type="submit" loading={isSubmitting}>Save changes</Button>
      </form>
      <LoginActivity />
    </>
  )
}

const ACTION_ICON = { login: LogIn, logout: LogOut, password_reset: KeyRound }

/** The user's own sign-ins / sign-outs (Profile › Login Activity). */
function LoginActivity() {
  const query = useQuery({ queryKey: ['admin', 'profile', 'logins'], queryFn: profileService.logins })
  useHashScroll(!query.isPending)
  const rows = query.data?.data ?? []
  return (
    <Card id="logins" title="Login activity" description="Your recent sign-ins. Contact an administrator if you see one you don't recognise." className="mt-6 max-w-3xl" bodyClassName="p-0">
      {query.isPending ? <p className="p-5 text-sm text-slate-400">Loading…</p> : !rows.length ? <p className="p-5 text-sm text-slate-500">No sign-ins recorded yet.</p> : (
        <ul className="divide-y divide-slate-100">
          {rows.map((r) => {
            const Icon = ACTION_ICON[r.action] ?? LogIn
            return (
              <li key={r.id} className="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                <span className="grid size-8 place-items-center rounded-lg bg-primary-50 text-primary"><Icon className="size-4" /></span>
                <span className="min-w-0 flex-1">
                  <span className="block font-medium text-slate-800">{r.description}</span>
                  <span className="block truncate text-xs text-slate-500">{r.ip_address}{r.user_agent ? ` · ${r.user_agent}` : ''}</span>
                </span>
                <span className="text-xs whitespace-nowrap text-slate-500">{formatDateTime(r.created_at)}</span>
              </li>
            )
          })}
        </ul>
      )}
    </Card>
  )
}
