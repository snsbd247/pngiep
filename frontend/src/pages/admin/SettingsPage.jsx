import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Send } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
import { useParams, useNavigate } from 'react-router-dom'
import { useHashScroll } from '@/hooks/useHashScroll'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormField, { inputClass } from '@/components/forms/FormField'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import FormSwitch from '@/components/forms/FormSwitch'
import FormTextarea from '@/components/forms/FormTextarea'
import ImageField from '@/components/forms/ImageField'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { cn, headline } from '@/lib/utils'
import { settingsService } from '@/services/admin'

const GROUP_ORDER = ['general', 'header', 'contact', 'social', 'appearance', 'seo', 'appointment', 'email', 'footer']
const GROUP_LABELS = { seo: 'SEO' }
const groupLabel = (g) => GROUP_LABELS[g] ?? headline(g)

/** Form control for one setting, chosen by its declared type. */
function SettingControl({ setting, control, register, errors, previews, setPreview }) {
  const name = `settings.${setting.key}`
  const error = errors?.settings?.[setting.key]?.message
  const common = { label: setting.label, help: setting.help, error }

  switch (setting.type) {
    case 'boolean':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field }) => <FormSwitch label={setting.label} description={setting.help} checked={!!field.value} onChange={field.onChange} />}
        />
      )
    case 'image':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field }) => (
            <ImageField
              {...common}
              value={field.value}
              previewUrl={previews[setting.key]}
              folder="branding"
              onChange={(path, url) => {
                field.onChange(path)
                setPreview(setting.key, url)
              }}
            />
          )}
        />
      )
    case 'color':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field }) => (
            <FormField {...common}>
              <div className="flex items-center gap-3">
                <input type="color" value={field.value || '#000000'} onChange={field.onChange} className="h-11 w-14 cursor-pointer rounded-xl border border-slate-200 bg-white p-1" aria-label={setting.label} />
                <input value={field.value || ''} onChange={field.onChange} className={cn(inputClass(error), 'h-11 max-w-40 font-mono uppercase')} />
              </div>
            </FormField>
          )}
        />
      )
    case 'select':
      return <FormSelect {...common} options={setting.options ?? []} {...register(name)} />
    case 'json':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field }) => {
            const selected = (field.value ?? []).map(String)
            return (
              <FormField {...common}>
                <div className="flex flex-wrap gap-2">
                  {(setting.options ?? []).map((o) => {
                    const checked = selected.includes(String(o.value))
                    return (
                      <button
                        key={o.value}
                        type="button"
                        aria-pressed={checked}
                        onClick={() =>
                          field.onChange(
                            checked ? (field.value ?? []).filter((v) => String(v) !== String(o.value)) : [...(field.value ?? []), o.value],
                          )
                        }
                        className={cn(
                          'rounded-full border px-4 py-2 text-sm font-medium transition',
                          checked ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300',
                        )}
                      >
                        {o.label}
                      </button>
                    )
                  })}
                </div>
              </FormField>
            )
          }}
        />
      )
    case 'textarea':
    case 'html':
      return <FormTextarea {...common} rows={3} {...register(name)} />
    case 'code':
      return <FormTextarea {...common} rows={6} className="font-mono" spellCheck={false} {...register(name)} />
    case 'password':
      return (
        <FormInput
          {...common}
          type="password"
          autoComplete="new-password"
          placeholder={setting.has_value ? '•••••••• (saved)' : ''}
          {...register(name)}
        />
      )
    case 'number':
      return <FormInput {...common} type="number" {...register(name)} />
    case 'email':
      return <FormInput {...common} type="email" {...register(name)} />
    case 'url':
      return <FormInput {...common} type="url" placeholder="https://" {...register(name)} />
    default:
      return <FormInput {...common} {...register(name)} />
  }
}

function TestMailCard() {
  const toast = useToast()
  const [email, setEmail] = useState('')
  const mutation = useMutation({
    mutationFn: () => settingsService.testMail(email),
    onSuccess: (res) => toast.success(res.message),
    onError: (e) => toast.error(e.message),
  })

  return (
    <Card title="Send a test email" description="Save your SMTP settings first, then send a test.">
      <div className="flex flex-col gap-3 sm:flex-row">
        <FormInput className="flex-1" type="email" placeholder="you@example.com" value={email} onChange={(e) => setEmail(e.target.value)} aria-label="Test email address" />
        <Button onClick={() => mutation.mutate()} loading={mutation.isPending} disabled={!email}>
          <Send className="size-4" /> Send test
        </Button>
      </div>
    </Card>
  )
}

export default function SettingsPage() {
  const { group: activeParam } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()
  const [previews, setPreviews] = useState({})

  const query = useQuery({ queryKey: ['admin', 'settings'], queryFn: settingsService.get })
  useHashScroll(!!query.data)
  const groups = query.data ? GROUP_ORDER.filter((g) => query.data[g]).concat(Object.keys(query.data).filter((g) => !GROUP_ORDER.includes(g))) : []
  const active = groups.includes(activeParam) ? activeParam : groups[0]

  const {
    register,
    control,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting, isDirty },
  } = useForm()
  const handleApiError = useApiErrorHandler(setError)

  useEffect(() => {
    if (!query.data) return
    const values = {}
    const urls = {}
    Object.values(query.data).flat().forEach((s) => {
      values[s.key] = s.type === 'password' ? '' : s.value ?? (s.type === 'json' ? [] : '')
      if (s.url) urls[s.key] = s.url
    })
    reset({ settings: values })
    setPreviews(urls)
  }, [query.data, reset])

  const onSubmit = async ({ settings }) => {
    const keys = (query.data[active] ?? []).map((s) => s.key)
    const payload = Object.fromEntries(keys.map((k) => [k, settings[k] === '' ? null : settings[k]]))
    try {
      const res = await settingsService.update(payload)
      queryClient.setQueryData(['admin', 'settings'], res.data)
      queryClient.invalidateQueries({ queryKey: ['site'] })
      toast.success(res.message)
    } catch (error) {
      handleApiError(error)
    }
  }

  return (
    <>
      <PageHeader title="Settings" description="Branding, contact details, SEO, email and booking rules for the whole website." />
      {query.isPending ? (
        <LoadingSkeleton variant="table" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : (
        <div className="grid gap-6 lg:grid-cols-[220px_1fr]">
          <nav className="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible" aria-label="Settings sections">
            {groups.map((g) => (
              <button
                key={g}
                type="button"
                onClick={() => navigate(`/admin/settings/${g}`)}
                className={cn(
                  'shrink-0 rounded-xl px-4 py-2.5 text-left text-sm font-medium transition',
                  g === active ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-white hover:text-primary',
                )}
              >
                {groupLabel(g)}
              </button>
            ))}
          </nav>

          <div className="space-y-6">
            <form onSubmit={handleSubmit(onSubmit)} noValidate>
              <Card
                title={groupLabel(active)}
                actions={
                  <Button type="submit" loading={isSubmitting} disabled={!isDirty}>
                    Save changes
                  </Button>
                }
              >
                <div className="grid gap-5 md:grid-cols-2">
                  {(query.data[active] ?? []).map((s) => (
                    <div key={s.key} id={`setting-${s.key}`} className={cn('scroll-mt-24 p-1', ['textarea', 'code', 'json', 'image', 'html'].includes(s.type) && 'md:col-span-2')}>
                      <SettingControl
                        setting={s}
                        control={control}
                        register={register}
                        errors={errors}
                        previews={previews}
                        setPreview={(key, url) => setPreviews((p) => ({ ...p, [key]: url }))}
                      />
                    </div>
                  ))}
                </div>
              </Card>
            </form>
            {active === 'email' && <TestMailCard />}
          </div>
        </div>
      )}
    </>
  )
}
