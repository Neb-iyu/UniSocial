"use client";

import React, { useState, useEffect } from "react";
import { useAuth } from "@/context/auth-context";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCard from "@/components/post-card";
import { LoadingSpinner } from "@/components/loading-spinner";
import { apiService } from "@/lib/api-service";
import type { User } from "@/types/user";
import type { Post } from "@/types/post";
import {
  UserPlus,
  UserMinus,
  Mail,
  Loader2,
  Calendar,
  Briefcase,
  MapPin,
  LinkIcon,
} from "lucide-react";
import { useToast } from "@/components/ui/use-toast";
import { UserAvatar } from "@/components/user-avatar";
import { formatDistanceToNow } from "date-fns";
import { motion } from "framer-motion";

export default function ProfilePage({
  params,
}: {
  params: { username: string };
}) {
  // Unwrap params using React.use()
  const unwrappedParams = React.use(params);
  const { username } = unwrappedParams;

  const [profile, setProfile] = useState<User | null>(null);
  const [posts, setPosts] = useState<Post[]>([]);
  const [likedPosts, setLikedPosts] = useState<Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isFollowing, setIsFollowing] = useState(false);
  const [followLoading, setFollowLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const { user } = useAuth();
  const { toast } = useToast();
  const isOwnProfile = user?.username === username;

  useEffect(() => {
    const fetchProfile = async () => {
      if (!username) return;

      setIsLoading(true);
      try {
        // Fetch user profile by username
        const userResponse = await apiService.getUserByUsername(username);
        setProfile(userResponse);

        // Check if the current user is following this profile
        if (user && !isOwnProfile && userResponse) {
          try {
            const followersResponse = await apiService.getFollowers(
              userResponse.public_uuid
            );
            const isFollowing = followersResponse.some(
              (follower: User) => follower.public_uuid === user.public_uuid
            );
            setIsFollowing(isFollowing);
          } catch (error) {
            console.error("Failed to check follow status:", error);
          }
        }
      } catch (error: any) {
        console.error("Failed to fetch profile:", error);
        toast({
          title: "Error",
          description:
            error.message || "Failed to load profile. Please try again.",
          variant: "destructive",
        });
      } finally {
        setIsLoading(false);
      }
    };

    if (username) {
      fetchProfile();
    }
  }, [username, user, isOwnProfile, toast]);

  const loadMorePosts = async () => {
    if (!profile || isLoadingMore || !hasMore) return;

    setIsLoadingMore(true);
    try {
      const nextPage = page + 1;
      const response = await apiService.get<Post[]>(
        `/posts?user=${profile.public_uuid}&page=${nextPage}&limit=10`
      );

      if (response.length > 0) {
        setPosts((prev) => [...prev, ...response]);
        setPage(nextPage);
      }

      setHasMore(response.length === 10);
    } catch (error) {
      console.error("Failed to load more posts:", error);
      toast({
        title: "Error",
        description: "Failed to load more posts",
        variant: "destructive",
      });
    } finally {
      setIsLoadingMore(false);
    }
  };

  const handleFollow = async () => {
    if (!profile) return;

    setFollowLoading(true);
    try {
      if (isFollowing) {
        await apiService.unfollowUser(profile.public_uuid);
      } else {
        await apiService.followUser(profile.public_uuid);
      }
      setIsFollowing(!isFollowing);

      // Update followers count
      setProfile((prev) => {
        if (!prev) return null;
        return {
          ...prev,
          followers_count: isFollowing
            ? (prev.followers_count || 1) - 1
            : (prev.followers_count || 0) + 1,
        };
      });

      toast({
        title: isFollowing ? "Unfollowed" : "Following",
        description: isFollowing
          ? `You are no longer following ${profile.username}`
          : `You are now following ${profile.username}`,
      });
    } catch (error: any) {
      console.error("Failed to follow/unfollow:", error);
      toast({
        title: "Error",
        description:
          error.message || "Failed to update follow status. Please try again.",
        variant: "destructive",
      });
    } finally {
      setFollowLoading(false);
    }
  };

  const handlePostDeleted = (postId: string) => {
    setPosts((prevPosts) =>
      prevPosts.filter((post) => post.public_uuid !== postId)
    );
    setLikedPosts((prevPosts) =>
      prevPosts.filter((post) => post.public_uuid !== postId)
    );

    // Update post count
    setProfile((prev) => {
      if (!prev) return null;
      return {
        ...prev,
        post_count: (prev.post_count || 1) - 1,
      };
    });
  };

  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <LoadingSpinner />
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="max-w-3xl mx-auto text-center py-12">
        <h1 className="text-2xl font-bold mb-4">User not found</h1>
        <p className="text-muted-foreground">
          The user you are looking for does not exist.
        </p>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto">
      <motion.div
        className="rounded-xl overflow-hidden border bg-card text-card-foreground shadow-lg mb-6"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
      >
        <div className="h-40 bg-gradient-to-r from-primary/30 via-primary/20 to-primary/10"></div>
        <div className="p-6">
          <div className="flex flex-col sm:flex-row items-center sm:items-end -mt-20 gap-4">
            <motion.div
              initial={{ scale: 0.8, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={{ duration: 0.3, delay: 0.2 }}
            >
              <UserAvatar
                user={profile}
                className="h-28 w-28 border-4 border-background shadow-xl"
              />
            </motion.div>
            <div className="flex-1 text-center sm:text-left mt-2 sm:mt-0">
              <h1 className="text-2xl font-bold">{profile.fullname}</h1>
              <p className="text-muted-foreground">@{profile.username}</p>
            </div>
            <div className="flex gap-2 mt-4 sm:mt-0">
              {!isOwnProfile && (
                <>
                  <Button variant="outline" size="sm" className="rounded-full">
                    <Mail className="h-4 w-4 mr-2" />
                    Message
                  </Button>
                  <Button
                    onClick={handleFollow}
                    disabled={followLoading}
                    variant={isFollowing ? "outline" : "default"}
                    size="sm"
                    className="rounded-full"
                  >
                    {followLoading ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : isFollowing ? (
                      <>
                        <UserMinus className="h-4 w-4 mr-2" />
                        Unfollow
                      </>
                    ) : (
                      <>
                        <UserPlus className="h-4 w-4 mr-2" />
                        Follow
                      </>
                    )}
                  </Button>
                </>
              )}
            </div>
          </div>

          <div className="mt-6">
            <p className="text-foreground/90">
              {profile.bio || "No bio available"}
            </p>

            <div className="flex flex-wrap gap-4 mt-4 text-sm">
              {profile.university_id && (
                <div className="flex items-center text-muted-foreground">
                  <Briefcase className="h-4 w-4 mr-1" />
                  <span>University ID: {profile.university_id}</span>
                </div>
              )}
              {profile.year_of_study && (
                <div className="flex items-center text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-1" />
                  <span>Year {profile.year_of_study}</span>
                </div>
              )}
              {profile.location && (
                <div className="flex items-center text-muted-foreground">
                  <MapPin className="h-4 w-4 mr-1" />
                  <span>{profile.location}</span>
                </div>
              )}
              {profile.website && (
                <div className="flex items-center text-muted-foreground">
                  <LinkIcon className="h-4 w-4 mr-1" />
                  <a
                    href={profile.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-primary"
                  >
                    {profile.website.replace(/^https?:\/\//, "")}
                  </a>
                </div>
              )}
              {profile.created_at && (
                <div className="flex items-center text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-1" />
                  <span>
                    Joined{" "}
                    {formatDistanceToNow(new Date(profile.created_at), {
                      addSuffix: true,
                    })}
                  </span>
                </div>
              )}
            </div>

            <div className="flex flex-wrap justify-center sm:justify-start gap-4 mt-6 text-sm">
              <div className="text-center px-4 py-2 bg-muted/50 rounded-full">
                <span className="font-bold">{profile.post_count || 0}</span>{" "}
                <span className="text-muted-foreground">Posts</span>
              </div>
              <div className="text-center px-4 py-2 bg-muted/50 rounded-full">
                <span className="font-bold">
                  {profile.followers_count || 0}
                </span>{" "}
                <span className="text-muted-foreground">Followers</span>
              </div>
              <div className="text-center px-4 py-2 bg-muted/50 rounded-full">
                <span className="font-bold">
                  {profile.following_count || 0}
                </span>{" "}
                <span className="text-muted-foreground">Following</span>
              </div>
            </div>
          </div>
        </div>
      </motion.div>

      <Tabs defaultValue="posts" className="w-full">
        <TabsList className="mb-6 w-full justify-center">
          <TabsTrigger value="posts" className="rounded-full flex-1">
            Posts
          </TabsTrigger>
          <TabsTrigger value="liked" className="rounded-full flex-1">
            Liked Posts
          </TabsTrigger>
          <TabsTrigger value="media" className="rounded-full flex-1">
            Media
          </TabsTrigger>
        </TabsList>

        <TabsContent value="posts">
          {posts.length > 0 ? (
            <div className="space-y-6">
              {posts.map((post, index) => (
                <motion.div
                  key={post.public_uuid}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.3, delay: index * 0.1 }}
                >
                  <PostCard post={post} onPostDeleted={handlePostDeleted} />
                </motion.div>
              ))}

              {hasMore && (
                <div className="flex justify-center py-4">
                  <Button
                    variant="outline"
                    onClick={loadMorePosts}
                    disabled={isLoadingMore}
                    className="rounded-full"
                  >
                    {isLoadingMore ? (
                      <>
                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                        Loading...
                      </>
                    ) : (
                      "Load More"
                    )}
                  </Button>
                </div>
              )}
            </div>
          ) : (
            <div className="text-center py-12 border rounded-lg">
              <p className="text-muted-foreground">No posts to display.</p>
              {isOwnProfile && (
                <p className="text-sm mt-2">
                  Create your first post to get started!
                </p>
              )}
            </div>
          )}
        </TabsContent>

        <TabsContent value="liked">
          {isOwnProfile ? (
            likedPosts.length > 0 ? (
              <div className="space-y-6">
                {likedPosts.map((post, index) => (
                  <motion.div
                    key={post.public_uuid}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: index * 0.1 }}
                  >
                    <PostCard post={post} onPostDeleted={handlePostDeleted} />
                  </motion.div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 border rounded-lg">
                <p className="text-muted-foreground">
                  You haven't liked any posts yet.
                </p>
              </div>
            )
          ) : (
            <div className="text-center py-12 border rounded-lg">
              <p className="text-muted-foreground">
                Liked posts are only visible to the profile owner.
              </p>
            </div>
          )}
        </TabsContent>

        <TabsContent value="media">
          <div className="text-center py-12 border rounded-lg">
            <p className="text-muted-foreground">
              Media posts will be displayed here.
            </p>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
