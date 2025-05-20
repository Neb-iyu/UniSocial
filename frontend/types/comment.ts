import type { User } from "./user"

export interface Comment {
  [x: string]: any
  public_uuid: string
  post_uuid: string
  user_uuid: string
  content: string
  like_count: number
  is_liked?: boolean
  user?: User
  created_at: string
  updated_at: string
}
