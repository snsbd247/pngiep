import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './index.css'

// Server-rendered SEO tags (from Laravel): drop only the ones react-helmet-async writes again
// (title, description, Open Graph text/image, robots), so there are no duplicates. The
// canonical link and the JSON-LD structured data stay: Google indexes the page *after*
// running JavaScript, so removing them would hide them from search. They are replaced on
// in-app navigation (see components/Seo.jsx).
document
  .querySelectorAll('title, [data-seo][name="description"], [data-seo][name="robots"], [data-seo][property="og:title"], [data-seo][property="og:description"], [data-seo][property="og:image"]')
  .forEach((el) => el.remove())

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
