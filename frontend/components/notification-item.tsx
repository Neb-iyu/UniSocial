"use client"

import type React from "react"

import { useState } from "react"
import Link from "next/link"
import { formatDistanceToNow } from "date-fns"
import { UserAvatar } from "@/components/user-avatar"
import { Button } from "@/components/ui/button"
import { Check } from "lucide-react"
import { apiService } from "@/lib/api-service"
import { useToast } from "@/components/ui/use-toast"
import type { Notification } from "@/types/notification"

interface NotificationItemProps {
  notification: Notification
  onMarkAsRead?: (id: string) => void
}

export function NotificationItem({ notification, onMarkAsRead }: NotificationItemProps) {
  const [isMarking, setIsMarking] = useState(false)
  const { toast } = useToast()

  const handleMarkAsRead = async (e: React.MouseEvent) => {
    e.preventDefault()
    e.stopPropagation()

    if (notification.is_read) return

    setIsMarking(true)
    try {
      await apiService.patch(`/notifications/${notification.public_uuid}`, {
        is_read: true,
      })

      if (onMarkAsRead) {
        onMarkAsRead(notification.public_uuid)
      }
    } catch (error) {
      console.error("Failed to mark notification as read:", error)
      toast({
        title: "Error",
        description: "Failed to mark notification as read",
        variant: "destructive",
      })
    } finally {
      setIsMarking(false)
    }
  }

  // Determine notification link based on type
  const getNotificationLink = () => {
    switch (notification.type) {
      case "like":
        return `/posts/${notification.post_uuid}`
      case "comment":
        return `/posts/${notification.post_uuid}`
      case "follow":
        return `/profile/${notification.sender_username}`
      case "mention":
        return `/posts/${notification.post_uuid}`
      default:
        return "#"
    }
  }

  // Generate notification message based on type
  const getNotificationMessage = () => {
    switch (notification.type) {
      case "like":
        return "liked your post"
      case "comment":
        return "commented on your post"
      case "follow":
        return "started following you"
      case "mention":
        return "mentioned you in a post"
      default:
        return "sent you a notification"
    }
  }

  return (
    <Link
      href={getNotificationLink()}
      className={`block p-4 border-b hover:bg-muted/50 transition-colors ${!notification.is_read ? "bg-primary/5" : ""}`}
    >
      <div className="flex items-start gap-3">
        <UserAvatar
          user={{
            username: notification.sender_username,
            profile_picture_url: notification.sender_profile_picture,
          }}
          size="md"
        />

        <div className="flex-1 min-w-0">
          <div className="flex justify-between items-start gap-2">
            <p className="text-sm font-medium">
              <span className="font-semibold">{notification.sender_fullname || notification.sender_username}</span>{" "}
              {getNotificationMessage()}
            </p>
            <span className="text-xs text-muted-foreground whitespace-nowrap">
              {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
            </span>
          </div>

          {notification.content && (
            <p className="text-sm text-muted-foreground mt-1 line-clamp-2">{notification.content}</p>
          )}
        </div>

        {!notification.is_read && (
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 shrink-0"
            onClick={handleMarkAsRead}
            disabled={isMarking}
          >
            <Check className="h-4 w-4" />
            <span className="sr-only">Mark as read</span>
          </Button>
        )}
      </div>
    </Link>
  )
}
