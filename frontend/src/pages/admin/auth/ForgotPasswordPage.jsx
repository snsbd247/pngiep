import { zodResolver } from '@hookform/resolvers/zod'
import { ArrowLeft, MailCheck, Mail } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router-dom'
import { z } from 'zod'
import FormInput from '@/components/forms/FormInput'
import Button from '@/components/ui/Button'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { authService } from '@/services/authService'
import AuthLayout from './AuthLayout'

const schema = z.object({ email: z.string().trim().email('Enter a valid email address.') })

export default function ForgotPasswordPage() {
  const [sentMessage, setSentMessage] = useState('')
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema) })
  const handleApiError = useApiErrorHandler(setError)

  const onSubmit = async ({ email }) => {
    try {
      const res = await authService.forgotPassword(email)
      setSentMessage(res.message)
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <AuthLayout title="Forgot password" subtitle="We'll email you a link to reset your password.">
      {sentMessage ? (
        <div className="rounded-2xl bg-emerald-50 p-5 text-center">
          <MailCheck className="mx-auto mb-3 size-8 text-emerald-600" />
          <p className="text-sm text-emerald-800">{sentMessage}</p>
        </div>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
          <FormInput label="Email" type="email" autoComplete="email" icon={Mail} {...register('email')} error={errors.email?.message} />
          <Button type="submit" variant="gradient" className="w-full" loading={isSubmitting}>
            Send reset link
          </Button>
        </form>
      )}
      <Link to="/admin/login" className="mt-6 flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 hover:text-primary">
        <ArrowLeft className="size-4" /> Back to sign in
      </Link>
    </AuthLayout>
  )
}
