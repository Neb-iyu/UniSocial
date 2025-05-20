"use client";

import type React from "react";
import { useState, useRef } from "react";
import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { UserAvatar } from "@/components/user-avatar";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardFooter } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { useAuth } from "@/context/auth-context";
import { apiService } from "@/lib/api-service";
import { useToast } from "@/components/ui/use-toast";
import {
  Heart,
  MessageCircle,
  Share,
  MoreHorizontal,
  Trash2,
  Loader2,
  Bookmark,
} from "lucide-react";
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
import { motion, AnimatePresence } from "framer-motion";
import CommentItem from "@/components/comment-item";
import type { Post } from "@/types/post";
import type { Comment } from "@/types/comment";

interface PostCardProps {
  post: Post;
  onPostDeleted?: (postId: string) => void;
}

export default function PostCard({ post, onPostDeleted }: PostCardProps) {
  const [isLiked, setIsLiked] = useState(post.is_liked || false);
  const [likeCount, setLikeCount] = useState(post.like_count || 0);
  const [isLiking, setIsLiking] = useState(false);
  const [showComments, setShowComments] = useState(false);
  const [comments, setComments] = useState<Comment[]>(post.comments || []);
  const [isLoadingComments, setIsLoadingComments] = useState(false);
  const [commentText, setCommentText] = useState("");
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isSaved, setIsSaved] = useState(false);
  const commentInputRef = useRef<HTMLTextAreaElement>(null);
  const { user } = useAuth();
  const { toast } = useToast();

  const isOwnPost = user?.public_uuid === post.user_uuid;
  const mediaUrls = Array.isArray(post.media_urls) ? post.media_urls : [];

  const handleLike = async () => {
    if (isLiking) return;

    setIsLiking(true);
    try {
      await apiService.togglePostLike(post.public_uuid);

      if (isLiked) {
        setLikeCount((prev) => prev - 1);
      } else {
        setLikeCount((prev) => prev + 1);
      }
      setIsLiked(!isLiked);
    } catch (error: any) {
      console.error("Failed to like/unlike post:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to like/unlike post",
        variant: "destructive",
      });
    } finally {
      setIsLiking(false);
    }
  };

  const toggleComments = async () => {
    if (!showComments && comments.length === 0) {
      await loadComments();
    }
    setShowComments(!showComments);

    if (!showComments && commentInputRef.current) {
      setTimeout(() => {
        commentInputRef.current?.focus();
      }, 300);
    }
  };

  const loadComments = async () => {
    setIsLoadingComments(true);
    try {
      const response = await apiService.getComments({
        post_uuid: post.public_uuid,
      });
      setComments(response.data);
    } catch (error: any) {
      console.error("Failed to load comments:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to load comments",
        variant: "destructive",
      });
    } finally {
      setIsLoadingComments(false);
    }
  };

  const handleCommentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!commentText.trim()) return;

    setIsSubmittingComment(true);
    try {
      const response = await apiService.createComment({
        post_uuid: post.public_uuid,
        content: commentText,
      });

      setComments((prev) => [response.data, ...prev]);
      setCommentText("");
      post.comment_count = (post.comment_count || 0) + 1;
    } catch (error: any) {
      console.error("Failed to submit comment:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to submit comment",
        variant: "destructive",
      });
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const handleDeletePost = async () => {
    setIsDeleting(true);
    try {
      await apiService.deletePost(post.public_uuid);

      if (onPostDeleted) {
        onPostDeleted(post.public_uuid);
      }

      toast({
        title: "Post deleted",
        description: "Your post has been deleted successfully",
      });
    } catch (error: any) {
      console.error("Failed to delete post:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to delete post",
        variant: "destructive",
      });
    } finally {
      setIsDeleting(false);
      setDeleteDialogOpen(false);
    }
  };

  const handleCommentDeleted = (commentId: string) => {
    setComments((prev) =>
      prev.filter((comment) => comment.public_uuid !== commentId)
    );
    post.comment_count = (post.comment_count || 0) - 1;
  };

  const toggleSave = () => {
    setIsSaved(!isSaved);
    toast({
      title: isSaved ? "Post removed from saved items" : "Post saved",
      description: isSaved
        ? "This post has been removed from your saved items"
        : "This post has been added to your saved items",
    });
  };

  const formatDate = (dateString: string) => {
    try {
      return formatDistanceToNow(new Date(dateString), { addSuffix: true });
    } catch (error) {
      return "some time ago";
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
    >
      <Card className="overflow-hidden border-none shadow-lg bg-gradient-to-br from-card to-card/80 backdrop-blur-sm">
        <CardContent className="p-5">
          <div className="flex justify-between items-start">
            <div className="flex gap-3">
              <Link href={`/profile/${post.user?.username || "user"}`}>
                <UserAvatar user={post.user} />
              </Link>
              <div>
                <Link
                  href={`/profile/${post.user?.username || "user"}`}
                  className="font-medium hover:underline text-foreground/90"
                >
                  {post.user?.fullname || post?.username || "Unknown User"}
                </Link>
                <p className="text-xs text-muted-foreground">
                  {formatDate(post.created_at)}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 rounded-full text-muted-foreground hover:text-foreground"
                onClick={toggleSave}
              >
                <Bookmark
                  className={`h-4 w-4 ${
                    isSaved ? "fill-current text-primary" : ""
                  }`}
                />
                <span className="sr-only">{isSaved ? "Unsave" : "Save"}</span>
              </Button>

              {isOwnPost && (
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 rounded-full"
                    >
                      <MoreHorizontal className="h-4 w-4" />
                      <span className="sr-only">Post options</span>
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="w-40">
                    <DropdownMenuItem
                      className="text-destructive focus:text-destructive"
                      onClick={() => setDeleteDialogOpen(true)}
                    >
                      <Trash2 className="h-4 w-4 mr-2" />
                      Delete post
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
            </div>
          </div>

          <div className="mt-4">
            <p className="whitespace-pre-wrap text-foreground/90 leading-relaxed">
              {post.content}
            </p>

            {mediaUrls.length > 0 && (
              <div className="mt-4 rounded-xl overflow-hidden">
                {mediaUrls.map((url, index) => {
                  const fullUrl = url?.startsWith("http")
                    ? url
                    : `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}${url}`;

                  return (
                    <img
                      key={index}
                      src={fullUrl || "/placeholder.svg"}
                      alt={`Post media ${index + 1}`}
                      className="w-full h-auto object-cover max-h-[500px]"
                      onError={(e) => {
                        e.currentTarget.src = "/placeholder.svg";
                        e.currentTarget.onerror = null;
                      }}
                    />
                  );
                })}
              </div>
            )}
          </div>

          <div className="flex items-center justify-between mt-4 pt-2">
            <div className="flex items-center gap-1">
              <Button
                variant="ghost"
                size="sm"
                className={`rounded-full px-3 ${
                  isLiked
                    ? "text-red-500"
                    : "text-muted-foreground hover:text-foreground"
                }`}
                onClick={handleLike}
                disabled={isLiking}
              >
                <Heart
                  className={`h-4 w-4 mr-1.5 ${isLiked ? "fill-current" : ""}`}
                />
                {likeCount > 0 && <span>{likeCount}</span>}
              </Button>

              <Button
                variant="ghost"
                size="sm"
                className="rounded-full px-3 text-muted-foreground hover:text-foreground"
                onClick={toggleComments}
              >
                <MessageCircle className="h-4 w-4 mr-1.5" />
                {post.comment_count > 0 && <span>{post.comment_count}</span>}
              </Button>

              <Button
                variant="ghost"
                size="sm"
                className="rounded-full px-3 text-muted-foreground hover:text-foreground"
              >
                <Share className="h-4 w-4 mr-1.5" />
                <span>Share</span>
              </Button>
            </div>
          </div>
        </CardContent>

        <AnimatePresence>
          {showComments && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              transition={{ duration: 0.3 }}
            >
              <CardFooter className="flex flex-col p-0">
                <Separator />

                <div className="p-5 w-full">
                  {user && (
                    <form onSubmit={handleCommentSubmit} className="mb-6">
                      <div className="flex gap-3">
                        <UserAvatar user={user} size="sm" />
                        <div className="flex-1">
                          <Textarea
                            ref={commentInputRef}
                            placeholder="Write a comment..."
                            value={commentText}
                            onChange={(e) => setCommentText(e.target.value)}
                            className="min-h-[60px] resize-none focus-visible:ring-primary"
                            disabled={isSubmittingComment}
                          />
                          <div className="flex justify-end mt-2">
                            <Button
                              type="submit"
                              size="sm"
                              className="rounded-full px-4"
                              disabled={
                                !commentText.trim() || isSubmittingComment
                              }
                            >
                              {isSubmittingComment ? (
                                <>
                                  <Loader2 className="h-3 w-3 mr-2 animate-spin" />
                                  Posting...
                                </>
                              ) : (
                                "Post"
                              )}
                            </Button>
                          </div>
                        </div>
                      </div>
                    </form>
                  )}

                  {isLoadingComments ? (
                    <div className="flex justify-center py-6">
                      <Loader2 className="h-6 w-6 animate-spin text-primary" />
                    </div>
                  ) : comments.length > 0 ? (
                    <div className="space-y-4">
                      {comments.map((comment) => (
                        <CommentItem
                          key={comment.public_uuid}
                          comment={comment}
                          onCommentDeleted={handleCommentDeleted}
                        />
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <p className="text-muted-foreground">
                        No comments yet. Be the first to comment!
                      </p>
                    </div>
                  )}
                </div>
              </CardFooter>
            </motion.div>
          )}
        </AnimatePresence>

        <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Delete post</AlertDialogTitle>
              <AlertDialogDescription>
                Are you sure you want to delete this post? This action cannot be
                undone.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel disabled={isDeleting}>
                Cancel
              </AlertDialogCancel>
              <AlertDialogAction
                onClick={handleDeletePost}
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
      </Card>
    </motion.div>
  );
}