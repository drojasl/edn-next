export interface SocialLink {
  platform: string
  value: string
}

export interface User {
  id: number
  name: string
  last_name: string
  codigo_amway: string
  is_account_holder: boolean
  email?: string
  slug?: string
  is_active?: boolean
  profile_picture?: string | null
  social_links?: SocialLink[]
  abo_link?: string
  client_link?: string
  my_digital_store?: string
}

export interface AuthResponse {
  user: User
  token: string
}
