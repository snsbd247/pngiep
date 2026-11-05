import { zodResolver } from '@hookform/resolvers/zod'
import { Lock } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { z } from 'zod'
import FormInput from '@/components/forms/FormInput'
import Button from '@/components/ui/Button'
import { useToast } from '@/components/ui/Toast'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { passwordRule, withPasswordConfirmation } from '@/lib/validation'
import { authService } from '@/services/authService'
import AuthLayout from './AuthLayout'

const schema = withPasswordConfirmation(z.object({ password: passwordRule, password_confirmation: z.string() }))

export default function ResetPasswordPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const toast = useToast()
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema) })
  const handleApiError = useApiErrorHandler(setError, (key) => (key === 'email' ? 'password' : key))

  const onSubmit = async (values) => {
    try {
      const res = await authService.resetPassword({ ...values, token: params.get('token'), email: params.get('email') })
      toast.success(res.message)
      navigate('/admin/login', { replace: true })
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <AuthLayout title="Set a new password" subtitle={params.get('email') || undefined}>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        <FormInput label="New password" type="password" autoComplete="new-password" icon={Lock} {...register('password')} error={errors.password?.message} />
        <FormInput label="Confirm password" type="password" autoComplete="new-password" icon={Lock} {...register('password_confirmation')} error={errors.password_confirmation?.message} />
        <Button type="submit" variant="gradient" className="w-full" loading={isSubmitting}>
          Reset password
        </Button>
      </form>
    </AuthLayout>
  )
}
