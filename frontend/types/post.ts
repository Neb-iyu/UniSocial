import type { User } from "./user"
import type { Comment } from "./comment"

export interface Post {
  public_uuid: string
  user_uuid: string
  content: string
  media_urls?: string[]
  like_count: number
  comment_count: number
  is_liked?: boolean
  user?: User
  comments?: Comment[]
  created_at: string
  updated_at: string
}
