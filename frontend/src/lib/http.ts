import axios from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'

export const http = axios.create({
  baseURL,
  // Send the session + XSRF cookies on every request.
  withCredentials: true,
  // Read the XSRF-TOKEN cookie and set the X-XSRF-TOKEN header automatically.
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let csrfReady = false

/**
 * Prime the CSRF cookie before the first mutating request. Sanctum sets
 * XSRF-TOKEN and the session cookie; subsequent mutating requests echo the
 * token back in the X-XSRF-TOKEN header (axios does this for us via
 * withXSRFToken). Idempotent — only the first call hits the network.
 */
export async function ensureCsrf(): Promise<void> {
  if (csrfReady) return
  await http.get('/sanctum/csrf-cookie')
  csrfReady = true
}
