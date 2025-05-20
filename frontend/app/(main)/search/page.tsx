"use client";

import { useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { LoadingSpinner } from "@/components/loading-spinner";
import { UserAvatar } from "@/components/user-avatar";
import PostCard from "@/components/post-card";
import { apiService } from "@/lib/api-service";
import { useToast } from "@/components/ui/use-toast";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { UserPlus, UserCheck, Loader2 } from "lucide-react";
import { useAuth } from "@/context/auth-context";
import type { User } from "@/types/user";
import type { Post } from "@/types/post";

export default function SearchPage() {
  const searchParams = useSearchParams();
  const query = searchParams?.get("q") || "";
  const [activeTab, setActiveTab] = useState("users");
  const [users, setUsers] = useState<User[]>([]);
  const [posts, setPosts] = useState<Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [followingStates, setFollowingStates] = useState<
    Record<string, boolean>
  >({});
  const [followLoading, setFollowLoading] = useState<Record<string, boolean>>(
    {}
  );
  const { user: currentUser } = useAuth();
  const { toast } = useToast();

  useEffect(() => {
    if (!query) return;

    const fetchSearchResults = async () => {
      setIsLoading(true);
      try {
        // Fetch users
        const usersResponse = (await apiService.getAllUsers({ q: query })) as User[] || [];
        setUsers(usersResponse);

        // Fetch posts
        const postsResponse = (await apiService.getPosts({ q: query })) as Post[] || [];
        setPosts(postsResponse);

        // Initialize following states
        if (currentUser && usersResponse) {
          const states: Record<string, boolean> = {};
          for (const user of usersResponse) {
            if (user.public_uuid !== currentUser.public_uuid) {
              try {
                const isFollowing = await checkIfFollowing(user.public_uuid);
                states[user.public_uuid] = isFollowing;
              } catch (error) {
                console.error(
                  `Failed to check following state for ${user.username}:`,
                  error
                );
                states[user.public_uuid] = false;
              }
            }
          }
          setFollowingStates(states);
        }
      } catch (error: any) {
        console.error("Search error:", error);
        toast({
          title: "Error",
          description: error.message || "Failed to fetch search results",
          variant: "destructive",
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchSearchResults();
  }, [query, currentUser, toast]);

  const checkIfFollowing = async (userUuid: string): Promise<boolean> => {
    try {
      const response = await apiService.getFollowers(userUuid);
      const followers = response as User[];
      return followers.some(
        (follower) => follower.public_uuid === currentUser?.public_uuid
      );
    } catch (error) {
      console.error("Failed to check follow status:", error);
      return false;
    }
  };

  const handleFollow = async (userUuid: string) => {
    if (!currentUser) return;

    setFollowLoading((prev) => ({ ...prev, [userUuid]: true }));
    try {
      const isFollowing = followingStates[userUuid];
      if (isFollowing) {
        await apiService.unfollowUser(userUuid);
      } else {
        await apiService.followUser(userUuid);
      }

      setFollowingStates((prev) => ({
        ...prev,
        [userUuid]: !isFollowing,
      }));

      toast({
        title: isFollowing ? "Unfollowed" : "Following",
        description: isFollowing
          ? `You are no longer following this user`
          : `You are now following this user`,
      });
    } catch (error: any) {
      console.error("Failed to follow/unfollow:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to update follow status",
        variant: "destructive",
      });
    } finally {
      setFollowLoading((prev) => ({ ...prev, [userUuid]: false }));
    }
  };

  const handlePostDeleted = (postId: string) => {
    setPosts((prevPosts) =>
      prevPosts.filter((post) => post.public_uuid !== postId)
    );
  };

  if (!query) {
    return (
      <div className="max-w-3xl mx-auto">
        <Card>
          <CardHeader>
            <CardTitle>Search</CardTitle>
            <CardDescription>Search for users and posts</CardDescription>
          </CardHeader>
          <CardContent>
            <p className="text-center py-8 text-muted-foreground">
              Enter a search term to find users and posts
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <LoadingSpinner />
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto">
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Search Results</CardTitle>
          <CardDescription>Results for "{query}"</CardDescription>
        </CardHeader>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="mb-6">
          <TabsTrigger value="users">Users ({users.length})</TabsTrigger>
          <TabsTrigger value="posts">Posts ({posts.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="users">
          {users.length > 0 ? (
            <div className="space-y-4">
              {users.map((user) => (
                <Card key={user.public_uuid} className="card-hover">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <Link href={`/profile/${user.username}`}>
                          <UserAvatar user={user} />
                        </Link>
                        <div>
                          <Link
                            href={`/profile/${user.username}`}
                            className="font-medium hover:underline"
                          >
                            {user.fullname || user.username}
                          </Link>
                          <p className="text-sm text-muted-foreground">
                            @{user.username}
                          </p>
                          {user.bio && (
                            <p className="text-sm mt-1">{user.bio}</p>
                          )}
                        </div>
                      </div>

                      {currentUser &&
                        user.public_uuid !== currentUser.public_uuid && (
                          <Button
                            variant={
                              followingStates[user.public_uuid]
                                ? "outline"
                                : "default"
                            }
                            size="sm"
                            onClick={() => handleFollow(user.public_uuid)}
                            disabled={followLoading[user.public_uuid]}
                          >
                            {followLoading[user.public_uuid] ? (
                              <Loader2 className="h-4 w-4 animate-spin" />
                            ) : followingStates[user.public_uuid] ? (
                              <>
                                <UserCheck className="h-4 w-4 mr-2" />
                                Following
                              </>
                            ) : (
                              <>
                                <UserPlus className="h-4 w-4 mr-2" />
                                Follow
                              </>
                            )}
                          </Button>
                        )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="p-6">
                <p className="text-center py-4 text-muted-foreground">
                  No users found for "{query}"
                </p>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="posts">
          {posts.length > 0 ? (
            <div className="space-y-6">
              {posts.map((post) => (
                <PostCard
                  key={post.public_uuid}
                  post={post}
                  onPostDeleted={handlePostDeleted}
                />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="p-6">
                <p className="text-center py-4 text-muted-foreground">
                  No posts found for "{query}"
                </p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
