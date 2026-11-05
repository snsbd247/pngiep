import { z } from 'zod'

/** Mirrors the backend rule Password::min(8)->letters()->numbers(). */
export const passwordRule = z
  .string()
  .min(8, 'Password must be at least 8 characters.')
  .regex(/[A-Za-z]/, 'Password must contain at least one letter.')
  .regex(/\d/, 'Password must contain at least one number.')

/** Adds a "passwords match" check to an object schema with password + password_confirmation. */
export function withPasswordConfirmation(schema) {
  return schema.refine((v) => !v.password || v.password === v.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
  })
}
