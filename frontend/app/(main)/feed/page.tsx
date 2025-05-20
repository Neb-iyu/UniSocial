"use client";

import { useState, useEffect, useRef } from "react";
import CreatePostForm from "@/components/create-post-form";
import PostCard from "@/components/post-card";
import { UserRecommendations } from "@/components/user-recommendations";
import { apiService } from "@/lib/api-service";
import { useToast } from "@/components/ui/use-toast";
import { LoadingSpinner } from "@/components/loading-spinner";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";
import type { Post } from "@/types/post";

export default function FeedPage() {
  const [posts, setPosts] = useState<Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const { toast } = useToast();
  const observerRef = useRef<IntersectionObserver | null>(null);
  const loadMoreRef = useRef<HTMLDivElement>(null);

  // Initial fetch of posts
  useEffect(() => {
    const fetchPosts = async () => {
      setIsLoading(true);
      try {
        const response = await apiService.getFeed({
          page: 1,
          limit: 10,
        });
        const posts = response.data as Post[];
        setPosts(posts);
        setHasMore(response.data.length === 10);
      } catch (error: any) {
        console.error("Failed to fetch posts:", error);
        toast({
          title: "Error",
          description:
            error.message || "Failed to load posts. Please try again.",
          variant: "destructive",
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchPosts();
  }, [toast]);

  // Infinite scrolling
  useEffect(() => {
    if (isLoading || isLoadingMore || !hasMore) return;

    observerRef.current = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        if (entry.isIntersecting && hasMore && !isLoadingMore) {
          loadMorePosts();
        }
      },
      { threshold: 1.0 }
    );

    if (loadMoreRef.current) {
      observerRef.current.observe(loadMoreRef.current);
    }

    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [isLoading, hasMore, isLoadingMore]);

  const loadMorePosts = async () => {
    if (isLoadingMore || !hasMore) return;

    setIsLoadingMore(true);
    try {
      const nextPage = page + 1;
      const response = await apiService.getFeed({
        page: nextPage,
        limit: 10,
      });
      const newPosts = response.data as Post[];

      if (newPosts.length > 0) {
        setPosts((prev) => [...prev, ...newPosts]);
        setPage(nextPage);
      }
      setHasMore(newPosts.length === 10);
    } catch (error: any) {
      console.error("Failed to load more posts:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to load more posts",
        variant: "destructive",
      });
    } finally {
      setIsLoadingMore(false);
    }
  };

  const handlePostCreated = (newPost: Post) => {
    setPosts((prev) => [newPost, ...prev]);
  };

  const handlePostDeleted = (postId: string) => {
    setPosts((prev) => prev.filter((post) => post.public_uuid !== postId));
  };

  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <LoadingSpinner />
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-6">
      <CreatePostForm onPostCreated={handlePostCreated} />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="md:col-span-2 space-y-6">
          {posts.length > 0 ? (
            <>
              {posts.map((post) => (
                <PostCard
                  key={post.public_uuid}
                  post={post}
                  onPostDeleted={handlePostDeleted}
                />
              ))}

              <div ref={loadMoreRef} className="flex justify-center py-4">
                {isLoadingMore ? (
                  <div className="flex items-center gap-2">
                    <Loader2 className="h-5 w-5 animate-spin text-primary" />
                    <span>Loading more posts...</span>
                  </div>
                ) : hasMore ? (
                  <Button variant="outline" onClick={loadMorePosts}>
                    Load More
                  </Button>
                ) : (
                  <p className="text-muted-foreground text-sm">
                    No more posts to load
                  </p>
                )}
              </div>
            </>
          ) : (
            <div className="text-center py-12 border rounded-lg">
              <p className="text-muted-foreground">No posts to display.</p>
              <p className="text-sm mt-2">
                Follow more users or create your first post to get started!
              </p>
            </div>
          )}
        </div>

        <div className="hidden md:block">
          <div className="sticky top-24">
            <UserRecommendations />
          </div>
        </div>
      </div>
    </div>
  );
}
