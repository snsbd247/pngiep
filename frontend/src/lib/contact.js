/** All phone numbers from Settings › Contact: the main one plus a comma-separated list. */
export function phoneNumbers(setting) {
  const others = String(setting('contact.contact_phone_secondary', '') ?? '').split(/[,;/]/)
  return [setting('contact.contact_phone'), ...others].map((p) => (p ?? '').trim()).filter(Boolean)
}
