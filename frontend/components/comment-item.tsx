"use client";

import { useState } from "react";
import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { UserAvatar } from "@/components/user-avatar";
import { Button } from "@/components/ui/button";
import { useToast } from "@/components/ui/use-toast";
import { useAuth } from "@/context/auth-context";
import { apiService } from "@/lib/api-service";
import type { Comment } from "@/types/comment";
import { Heart, MoreHorizontal, Trash2, Loader2 } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { motion } from "framer-motion";

interface CommentItemProps {
  comment: Comment;
  onCommentDeleted: (commentId: string) => void;
}

export default function CommentItem({
  comment,
  onCommentDeleted,
}: CommentItemProps) {
  const { user } = useAuth();
  const { toast } = useToast();
  const [isLiked, setIsLiked] = useState(comment.is_liked || false);
  const [likesCount, setLikesCount] = useState(comment.like_count || 0);
  const [isDeleting, setIsDeleting] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

  const toggleLike = async () => {
    try {
      const response = await apiService.post<{
        is_liked: boolean;
        likes_count: number;
      }>(`/comments/${comment.public_uuid}/like`);
      setIsLiked(response.is_liked);
      setLikesCount(response.likes_count);
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to like comment",
        variant: "destructive",
      });
    }
  };

  const handleDeleteComment = async () => {
    setIsDeleting(true);
    try {
      await apiService.delete(`/comments/${comment.public_uuid}`);

      toast({
        title: "Comment deleted",
        description: "Your comment has been deleted successfully",
      });

      onCommentDeleted(comment.public_uuid);
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to delete comment",
        variant: "destructive",
      });
    } finally {
      setIsDeleting(false);
      setDeleteDialogOpen(false);
    }
  };

  const formatDate = (dateString: string) => {
    try {
      return formatDistanceToNow(new Date(dateString), { addSuffix: true });
    } catch (error) {
      return "some time ago";
    }
  };

  const isOwnComment = user?.public_uuid === comment.user_uuid;

  return (
    <motion.div
      className="flex gap-3"
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.2 }}
    >
      <Link href={`/profile/${comment.username}`}>
        <UserAvatar
          user={{
            username: comment.username,
            profile_picture_url: comment.profile_picture_url,
            public_uuid: comment.user_uuid,
            email: "", // Provide a default or fetch the actual email
            fullname: "", // Provide a default or fetch the actual fullname
            created_at: "", // Provide a default or fetch the actual created_at
            updated_at: "", // Provide a default or fetch the actual updated_at
          }}
          size="sm"
        />
      </Link>

      <div className="flex-1 space-y-1">
        <div className="bg-muted/50 backdrop-blur-sm rounded-xl p-3">
          <div className="flex items-center justify-between">
            <Link
              href={`/profile/${comment.username}`}
              className="font-semibold text-sm hover:underline"
            >
              {comment.username}
            </Link>

            {isOwnComment && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-6 w-6 p-0 rounded-full"
                  >
                    <MoreHorizontal className="h-3 w-3" />
                    <span className="sr-only">More options</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-36">
                  <DropdownMenuItem
                    className="text-destructive focus:text-destructive"
                    onClick={() => setDeleteDialogOpen(true)}
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>

          <p className="text-sm mt-1">{comment.content}</p>
        </div>

        <div className="flex items-center gap-4 text-xs text-muted-foreground pl-2">
          <Button
            variant="ghost"
            size="sm"
            className={`h-6 px-2 flex items-center gap-1 ${
              isLiked ? "text-red-500" : ""
            }`}
            onClick={toggleLike}
          >
            <Heart className={`h-3 w-3 ${isLiked ? "fill-current" : ""}`} />
            <span>{likesCount}</span>
          </Button>

          <span>{formatDate(comment.created_at)}</span>

          {comment.is_edited && <span>(edited)</span>}
        </div>
      </div>

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete comment</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this comment? This action cannot
              be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isDeleting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteComment}
              disabled={isDeleting}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {isDeleting ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Deleting...
                </>
              ) : (
                "Delete"
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </motion.div>
  );
}
