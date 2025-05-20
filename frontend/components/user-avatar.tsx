import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import type { User } from "@/types/user";

interface UserAvatarProps {
  user?: User | null;
  size?: "default" | "sm" | "lg";
  className?: string;
}

export function UserAvatar({
  user,
  size = "default",
  className,
}: UserAvatarProps) {
  const sizeClasses = {
    default: "h-10 w-10",
    sm: "h-8 w-8",
    lg: "h-14 w-14",
  };

  const getInitials = () => {
    if (!user) return "U";

    if (user.fullname) {
      const names = user.fullname.split(" ");
      if (names.length >= 2) {
        return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
      }
      return user.fullname.substring(0, 2).toUpperCase();
    }

    return "User";
  };

  // Handle profile picture URL properly
  const getProfilePictureUrl = () => {
    if (!user || !user.profile_picture_url) return "";

    // If the URL is already absolute (starts with http or https), use it directly
    if (user.profile_picture_url.startsWith("http")) {
      return user.profile_picture_url;
    }

    // Otherwise, prepend the API base URL
    const baseUrl = "http://localhost:8000";
    return `${baseUrl}${user.profile_picture_url}`;
  };

  return (
    <Avatar
      className={`${sizeClasses[size]} ${
        className || ""
      } ring-2 ring-primary/10 ring-offset-2 ring-offset-background transition-all duration-300 hover:ring-primary/30`}
    >
      <AvatarImage
        src={getProfilePictureUrl() || "/placeholder.svg"}
        alt={user?.username || "User"}
      />
      <AvatarFallback className="bg-gradient-to-br from-primary/20 to-primary/30 text-primary">
        {getInitials()}
      </AvatarFallback>
    </Avatar>
  );
}
