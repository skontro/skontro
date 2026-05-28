export type Role = 'owner' | 'admin' | 'member'

export interface Tenant {
  id: string
  name: string
  slug: string
}

export interface User {
  id: string
  name: string
  email: string
  role: Role
  tenant: Tenant
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterPayload {
  company_name: string
  name: string
  email: string
  password: string
  password_confirmation: string
}

export type AuthStatus = 'idle' | 'loading' | 'authenticated' | 'guest'

export interface ValidationErrors {
  [field: string]: string[]
}
