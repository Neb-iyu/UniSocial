export interface Notification {
  public_uuid: string
  user_uuid: string
  sender_uuid: string
  sender_username: string
  sender_fullname?: string
  sender_profile_picture?: string
  type: "like" | "comment" | "follow" | "mention" | string
  content?: string
  post_uuid?: string
  comment_uuid?: string
  is_read: boolean
  created_at: string
  updated_at: string
}
