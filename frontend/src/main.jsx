import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './index.css'

// Server-rendered SEO tags (from Laravel) are for crawlers on first load;
// once React mounts, react-helmet-async owns the <head>, so drop them to avoid duplicates.
document.querySelectorAll('[data-seo]').forEach((el) => el.remove())

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
