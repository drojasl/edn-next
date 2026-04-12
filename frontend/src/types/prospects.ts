export interface Prospect {
  id: number
  name: string
  phone: string
  email: string
  city?: string
  country?: string
  is_reviewed: boolean
  created_at: string
}
