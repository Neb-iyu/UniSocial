"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { UserAvatar } from "@/components/user-avatar";
import { apiService } from "@/lib/api-service";
import { useToast } from "@/components/ui/use-toast";
import { useAuth } from "@/context/auth-context";
import { UserPlus, UserCheck, Loader2, Users } from "lucide-react";
import { motion } from "framer-motion";
import type { User } from "@/types/user";

interface UsersResponse {
  data: User[];
  message?: string;
  status: string;
}

interface FollowResponse {
  data: {
    action: "follow" | "unfollow";
    following_count: number;
    followers_count: number;
    target_following_count: number;
    target_followers_count: number;
  };
  message?: string;
  status: string;
}

export function UserRecommendations() {
  const [users, setUsers] = useState<User[]>([]);
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
    const fetchRandomUsers = async () => {
      setIsLoading(true);
      try {
        const response = await apiService.get<UsersResponse>("/users", {
          params: {
            limit: 5, // Fetch more to ensure we get enough after filtering
            page: Math.floor(Math.random() * 5) + 1,
          },
        });

        // Filter out current user and ensure we have 3 users
        const usersData = (response?.data || [])
          .filter((user) => user.public_uuid !== currentUser?.public_uuid)
          .slice(0, 3);

        setUsers(usersData);

        if (currentUser && usersData.length) {
          const states: Record<string, boolean> = {};
          for (const user of usersData) {
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
          setFollowingStates(states);
        }
      } catch (error) {
        console.error("Failed to fetch random users:", error);
        toast({
          title: "Error",
          description: "Failed to load recommendations",
          variant: "destructive",
        });
      } finally {
        setIsLoading(false);
      }
    };

    if (currentUser) {
      fetchRandomUsers();
    }
  }, [currentUser, toast]);

  const checkIfFollowing = async (userUuid: string): Promise<boolean> => {
    try {
      const response = await apiService.get<UsersResponse>(
        `/users/${userUuid}/followers`
      );
      const followers = response?.data || [];
      return followers.some(
        (follower: User) => follower.public_uuid === currentUser?.public_uuid
      );
    } catch (error) {
      console.error("Failed to check follow status:", error);
      return false;
    }
  };

  const handleFollowToggle = async (userUuid: string) => {
    if (!currentUser) {
      toast({
        title: "Authentication required",
        description: "Please login to follow users",
        variant: "destructive",
      });
      return;
    }

    setFollowLoading((prev) => ({ ...prev, [userUuid]: true }));

    try {
      const isFollowing = followingStates[userUuid];
      const response = await apiService.post<FollowResponse>(
        `/users/${userUuid}/follow`,
        {}
      );

      if (response?.status === "success") {
        const { action, target_followers_count } = response.data;
        setFollowingStates((prev) => ({
          ...prev,
          [userUuid]: action === "follow",
        }));

        // Update local user data
        setUsers((prevUsers) =>
          prevUsers.map((user) =>
            user.public_uuid === userUuid
              ? {
                  ...user,
                  followers_count: target_followers_count,
                }
              : user
          )
        );

        toast({
          title: action === "follow" ? "Following" : "Unfollowed",
          description:
            action === "follow"
              ? `You are now following this user`
              : `You are no longer following this user`,
        });
      } else {
        throw new Error(response?.message || "Failed to update follow status");
      }
    } catch (error: any) {
      console.error("Failed to toggle follow:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to update follow status",
        variant: "destructive",
      });
    } finally {
      setFollowLoading((prev) => ({ ...prev, [userUuid]: false }));
    }
  };

  if (isLoading) {
    return (
      <Card className="border-none shadow-lg bg-gradient-to-br from-card to-card/80 backdrop-blur-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Users className="h-5 w-5" />
            Who to follow
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="flex items-center gap-3 animate-pulse">
                <div className="h-10 w-10 rounded-full bg-muted"></div>
                <div className="space-y-2 flex-1">
                  <div className="h-4 bg-muted rounded w-24"></div>
                  <div className="h-3 bg-muted rounded w-16"></div>
                </div>
                <div className="h-8 w-20 bg-muted rounded"></div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!users.length) {
    return null;
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
    >
      <Card className="border-none shadow-lg bg-gradient-to-br from-card to-card/80 backdrop-blur-sm">
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Users className="h-5 w-5" />
            Who to follow
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {users.map((user, index) => (
              <motion.div
                key={user.public_uuid}
                className="flex items-center gap-3"
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.2, delay: index * 0.1 }}
              >
                <Link href={`/profile/${user.username}`}>
                  <UserAvatar user={user} size="sm" />
                </Link>
                <div className="flex-1 min-w-0">
                  <Link
                    href={`/profile/${user.username}`}
                    className="font-medium hover:underline block truncate"
                  >
                    {user.fullname || user.username}
                  </Link>
                  <p className="text-xs text-muted-foreground truncate">
                    @{user.username}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {user.followers_count || 0} followers
                  </p>
                </div>
                <Button
                  variant={
                    followingStates[user.public_uuid] ? "outline" : "default"
                  }
                  size="sm"
                  className="whitespace-nowrap rounded-full"
                  onClick={() => handleFollowToggle(user.public_uuid)}
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
              </motion.div>
            ))}
          </div>
          <div className="mt-4 pt-4 border-t">
            <Link
              href="/search"
              className="text-sm text-primary hover:underline flex items-center justify-center"
            >
              View more suggestions
            </Link>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}
