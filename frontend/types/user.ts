export interface User {
  public_uuid: string;
  username: string;
  email: string;
  fullname: string;
  bio?: string;
  profile_picture_url?: string;
  university_id?: string;
  year_of_study?: string | number;
  gender?: string;
  role?: string;
  is_verified?: boolean;
  post_count?: number;
  followers_count?: number;
  following_count?: number;
  location?: string;
  website?: string;
  created_at: string;
  updated_at: string;
}
