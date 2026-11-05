import { zodResolver } from '@hookform/resolvers/zod'
import { Lock, Mail } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { z } from 'zod'
import FormInput from '@/components/forms/FormInput'
import Button from '@/components/ui/Button'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import AuthLayout from './AuthLayout'

const schema = z.object({
  email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
  remember: z.boolean().optional(),
})

export default function LoginPage() {
  const { login } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const location = useLocation()
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema), defaultValues: { email: '', password: '', remember: false } })
  const handleApiError = useApiErrorHandler(setError)

  const onSubmit = async (values) => {
    try {
      const user = await login(values)
      toast.success(`Welcome back, ${user.name}!`)
      navigate(location.state?.from?.pathname || '/admin', { replace: true })
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <AuthLayout title="Sign in" subtitle="Enter your credentials to access the admin panel.">
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        <FormInput label="Email" type="email" autoComplete="email" icon={Mail} {...register('email')} error={errors.email?.message} />
        <FormInput label="Password" type="password" autoComplete="current-password" icon={Lock} {...register('password')} error={errors.password?.message} />
        <div className="flex items-center justify-between">
          <label className="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" className="size-4 rounded border-slate-300 accent-primary" {...register('remember')} />
            Remember me
          </label>
          <Link to="/admin/forgot-password" className="text-sm font-medium text-primary hover:underline">
            Forgot password?
          </Link>
        </div>
        <Button type="submit" variant="gradient" className="w-full" loading={isSubmitting}>
          Sign in
        </Button>
      </form>
    </AuthLayout>
  )
}
