"use client"

import { useEffect, useState } from "react"
import { useAuth } from "@/context/auth-context"
import { apiService } from "@/lib/api-service"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { LoadingSpinner } from "@/components/loading-spinner"
import { useToast } from "@/components/ui/use-toast"
import { Badge } from "@/components/ui/badge"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { UserAvatar } from "@/components/user-avatar"
import {
  AlertCircle,
  CheckCircle,
  Shield,
  ShieldAlert,
  Users,
  FileText,
  Trash2,
  Ban,
  Loader2,
  Search,
  RefreshCw,
  Download,
} from "lucide-react"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import type { User } from "@/types/user"
import type { Post } from "@/types/post"

export default function AdminPage() {
  const { user } = useAuth()
  const [isAdmin, setIsAdmin] = useState(false)
  const [isLoading, setIsLoading] = useState(true)
  const [users, setUsers] = useState<User[]>([])
  const [posts, setPosts] = useState<Post[]>([])
  const [filteredUsers, setFilteredUsers] = useState<User[]>([])
  const [filteredPosts, setFilteredPosts] = useState<Post[]>([])
  const [userSearch, setUserSearch] = useState("")
  const [postSearch, setPostSearch] = useState("")
  const [stats, setStats] = useState({
    totalUsers: 0,
    totalPosts: 0,
    activeUsers: 0,
    reportedContent: 0,
  })
  const [isRefreshing, setIsRefreshing] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [itemToDelete, setItemToDelete] = useState<{ id: string; type: "user" | "post" } | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const { toast } = useToast()

  useEffect(() => {
    const checkAdminStatus = async () => {
      if (!user) return

      try {
        // This is a simplified check - in a real app, you'd have a proper endpoint
        const response = await apiService.get("/me")
        const isUserAdmin = response.is_admin || false
        setIsAdmin(isUserAdmin)

        if (isUserAdmin) {
          await fetchAdminData()
        }
      } catch (error) {
        console.error("Failed to check admin status:", error)
      } finally {
        setIsLoading(false)
      }
    }

    checkAdminStatus()
  }, [user])

  useEffect(() => {
    if (userSearch) {
      const filtered = users.filter(
        (user) =>
          user.username.toLowerCase().includes(userSearch.toLowerCase()) ||
          (user.fullname && user.fullname.toLowerCase().includes(userSearch.toLowerCase())),
      )
      setFilteredUsers(filtered)
    } else {
      setFilteredUsers(users)
    }
  }, [userSearch, users])

  useEffect(() => {
    if (postSearch) {
      const filtered = posts.filter((post) => post.content.toLowerCase().includes(postSearch.toLowerCase()))
      setFilteredPosts(filtered)
    } else {
      setFilteredPosts(posts)
    }
  }, [postSearch, posts])

  const fetchAdminData = async () => {
    setIsRefreshing(true)
    try {
      // Fetch users
      const usersResponse = await apiService.get("/users")
      setUsers(usersResponse || [])
      setFilteredUsers(usersResponse || [])

      // Fetch all posts (simplified - in a real app, you'd have pagination)
      const postsResponse = await apiService.get("/posts")
      setPosts(postsResponse || [])
      setFilteredPosts(postsResponse || [])

      // Set stats
      setStats({
        totalUsers: usersResponse?.length || 0,
        totalPosts: postsResponse?.length || 0,
        activeUsers: usersResponse?.filter((u: User) => !u.is_deleted).length || 0,
        reportedContent: 0, // This would come from a real endpoint
      })
    } catch (error) {
      console.error("Failed to fetch admin data:", error)
      toast({
        title: "Error",
        description: "Failed to load admin data",
        variant: "destructive",
      })
    } finally {
      setIsRefreshing(false)
    }
  }

  const handleDeleteItem = (id: string, type: "user" | "post") => {
    setItemToDelete({ id, type })
    setDeleteDialogOpen(true)
  }

  const confirmDelete = async () => {
    if (!itemToDelete) return

    setIsDeleting(true)
    try {
      if (itemToDelete.type === "user") {
        await apiService.delete(`/users/${itemToDelete.id}`)
        setUsers((prev) => prev.filter((user) => user.public_uuid !== itemToDelete.id))
        setFilteredUsers((prev) => prev.filter((user) => user.public_uuid !== itemToDelete.id))
        toast({
          title: "User deleted",
          description: "The user has been deleted successfully",
        })
      } else {
        await apiService.delete(`/posts/${itemToDelete.id}`)
        setPosts((prev) => prev.filter((post) => post.public_uuid !== itemToDelete.id))
        setFilteredPosts((prev) => prev.filter((post) => post.public_uuid !== itemToDelete.id))
        toast({
          title: "Post deleted",
          description: "The post has been deleted successfully",
        })
      }
    } catch (error) {
      console.error("Failed to delete item:", error)
      toast({
        title: "Error",
        description: `Failed to delete ${itemToDelete.type}`,
        variant: "destructive",
      })
    } finally {
      setIsDeleting(false)
      setDeleteDialogOpen(false)
      setItemToDelete(null)
    }
  }

  const handleBanUser = async (userId: string) => {
    try {
      await apiService.post(`/users/${userId}/ban`)
      // Update user in the list
      setUsers((prev) => prev.map((user) => (user.public_uuid === userId ? { ...user, is_banned: true } : user)))
      setFilteredUsers((prev) =>
        prev.map((user) => (user.public_uuid === userId ? { ...user, is_banned: true } : user)),
      )
      toast({
        title: "User banned",
        description: "The user has been banned successfully",
      })
    } catch (error) {
      console.error("Failed to ban user:", error)
      toast({
        title: "Error",
        description: "Failed to ban user",
        variant: "destructive",
      })
    }
  }

  const handleUnbanUser = async (userId: string) => {
    try {
      await apiService.post(`/users/${userId}/unban`)
      // Update user in the list
      setUsers((prev) => prev.map((user) => (user.public_uuid === userId ? { ...user, is_banned: false } : user)))
      setFilteredUsers((prev) =>
        prev.map((user) => (user.public_uuid === userId ? { ...user, is_banned: false } : user)),
      )
      toast({
        title: "User unbanned",
        description: "The user has been unbanned successfully",
      })
    } catch (error) {
      console.error("Failed to unban user:", error)
      toast({
        title: "Error",
        description: "Failed to unban user",
        variant: "destructive",
      })
    }
  }

  const handleExportData = (type: "users" | "posts") => {
    const data = type === "users" ? users : posts
    const fileName = `unifyze_${type}_${new Date().toISOString().split("T")[0]}.json`

    // Create a blob with the data
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" })

    // Create a download link and trigger it
    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = fileName
    document.body.appendChild(a)
    a.click()

    // Clean up
    URL.revokeObjectURL(url)
    document.body.removeChild(a)

    toast({
      title: "Export successful",
      description: `${type.charAt(0).toUpperCase() + type.slice(1)} data has been exported to ${fileName}`,
    })
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-[50vh]">
        <LoadingSpinner />
      </div>
    )
  }

  if (!isAdmin) {
    return (
      <div className="flex flex-col items-center justify-center h-[50vh] text-center">
        <ShieldAlert className="h-16 w-16 text-destructive mb-4" />
        <h1 className="text-2xl font-bold mb-2">Access Denied</h1>
        <p className="text-muted-foreground max-w-md">
          You don't have permission to access the admin panel. Please contact an administrator if you believe this is an
          error.
        </p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Admin Dashboard</h1>
        <p className="text-muted-foreground">Manage users, posts, and system settings.</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalUsers}</div>
            <p className="text-xs text-muted-foreground">{stats.activeUsers} active users</p>
          </CardContent>
        </Card>

        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Posts</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalPosts}</div>
            <p className="text-xs text-muted-foreground">Content across the platform</p>
          </CardContent>
        </Card>

        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Reported Content</CardTitle>
            <AlertCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.reportedContent}</div>
            <p className="text-xs text-muted-foreground">Items requiring review</p>
          </CardContent>
        </Card>

        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">System Status</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">Healthy</div>
            <p className="text-xs text-muted-foreground">All systems operational</p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="users">
        <TabsList>
          <TabsTrigger value="users">Users</TabsTrigger>
          <TabsTrigger value="posts">Posts</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>

        <TabsContent value="users" className="space-y-4">
          <Card className="card-hover">
            <CardHeader>
              <CardTitle>User Management</CardTitle>
              <CardDescription>View and manage all users in the system.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex justify-between items-center mb-4">
                <div className="relative w-full max-w-sm">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Search users..."
                    className="pl-10"
                    value={userSearch}
                    onChange={(e) => setUserSearch(e.target.value)}
                  />
                </div>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => handleExportData("users")}>
                    <Download className="h-4 w-4 mr-2" />
                    Export
                  </Button>
                  <Button variant="outline" size="sm" onClick={fetchAdminData} disabled={isRefreshing}>
                    <RefreshCw className={`h-4 w-4 mr-2 ${isRefreshing ? "animate-spin" : ""}`} />
                    Refresh
                  </Button>
                </div>
              </div>

              <div className="rounded-md border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-muted/50">
                      <th className="py-3 px-4 text-left font-medium">User</th>
                      <th className="py-3 px-4 text-left font-medium">Email</th>
                      <th className="py-3 px-4 text-left font-medium">Status</th>
                      <th className="py-3 px-4 text-left font-medium">Joined</th>
                      <th className="py-3 px-4 text-right font-medium">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map((user) => (
                      <tr key={user.public_uuid} className="border-b">
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-3">
                            <UserAvatar user={user} size="sm" />
                            <div>
                              <p className="font-medium">{user.fullname || user.username}</p>
                              <p className="text-xs text-muted-foreground">@{user.username}</p>
                            </div>
                          </div>
                        </td>
                        <td className="py-3 px-4 text-muted-foreground">{user.email}</td>
                        <td className="py-3 px-4">
                          {user.is_banned ? (
                            <Badge variant="destructive">Banned</Badge>
                          ) : user.is_deleted ? (
                            <Badge variant="outline">Deleted</Badge>
                          ) : (
                            <Badge
                              variant="outline"
                              className="bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400"
                            >
                              Active
                            </Badge>
                          )}
                        </td>
                        <td className="py-3 px-4 text-muted-foreground">
                          {new Date(user.created_at).toLocaleDateString()}
                        </td>
                        <td className="py-3 px-4 text-right">
                          <div className="flex justify-end gap-2">
                            {user.is_banned ? (
                              <Button variant="outline" size="sm" onClick={() => handleUnbanUser(user.public_uuid)}>
                                Unban
                              </Button>
                            ) : (
                              <Button variant="outline" size="sm" onClick={() => handleBanUser(user.public_uuid)}>
                                <Ban className="h-4 w-4 mr-2" />
                                Ban
                              </Button>
                            )}
                            <Button
                              variant="destructive"
                              size="sm"
                              onClick={() => handleDeleteItem(user.public_uuid, "user")}
                            >
                              <Trash2 className="h-4 w-4 mr-2" />
                              Delete
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="posts" className="space-y-4">
          <Card className="card-hover">
            <CardHeader>
              <CardTitle>Post Management</CardTitle>
              <CardDescription>View and manage all posts in the system.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex justify-between items-center mb-4">
                <div className="relative w-full max-w-sm">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Search posts..."
                    className="pl-10"
                    value={postSearch}
                    onChange={(e) => setPostSearch(e.target.value)}
                  />
                </div>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => handleExportData("posts")}>
                    <Download className="h-4 w-4 mr-2" />
                    Export
                  </Button>
                  <Button variant="outline" size="sm" onClick={fetchAdminData} disabled={isRefreshing}>
                    <RefreshCw className={`h-4 w-4 mr-2 ${isRefreshing ? "animate-spin" : ""}`} />
                    Refresh
                  </Button>
                </div>
              </div>

              <div className="rounded-md border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-muted/50">
                      <th className="py-3 px-4 text-left font-medium">Author</th>
                      <th className="py-3 px-4 text-left font-medium">Content</th>
                      <th className="py-3 px-4 text-left font-medium">Date</th>
                      <th className="py-3 px-4 text-left font-medium">Status</th>
                      <th className="py-3 px-4 text-right font-medium">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredPosts.map((post) => (
                      <tr key={post.public_uuid} className="border-b">
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-3">
                            <UserAvatar user={post.user} size="sm" />
                            <div>
                              <p className="font-medium">{post.user?.fullname || post.user?.username}</p>
                              <p className="text-xs text-muted-foreground">@{post.user?.username}</p>
                            </div>
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          <p className="truncate max-w-xs">{post.content}</p>
                        </td>
                        <td className="py-3 px-4 text-muted-foreground">
                          {new Date(post.created_at).toLocaleDateString()}
                        </td>
                        <td className="py-3 px-4">
                          {post.is_deleted ? (
                            <Badge variant="outline">Deleted</Badge>
                          ) : (
                            <Badge
                              variant="outline"
                              className="bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400"
                            >
                              Active
                            </Badge>
                          )}
                        </td>
                        <td className="py-3 px-4 text-right">
                          <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => handleDeleteItem(post.public_uuid, "post")}
                          >
                            <Trash2 className="h-4 w-4 mr-2" />
                            Delete
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="reports" className="space-y-4">
          <Card className="card-hover">
            <CardHeader>
              <CardTitle>Content Reports</CardTitle>
              <CardDescription>Review and moderate reported content.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex flex-col items-center justify-center py-10 text-center">
                <Shield className="h-12 w-12 text-muted-foreground mb-4" />
                <h3 className="text-lg font-medium mb-2">No Reports</h3>
                <p className="text-muted-foreground max-w-md">
                  There are currently no reported items requiring your attention.
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="settings" className="space-y-4">
          <Card className="card-hover">
            <CardHeader>
              <CardTitle>System Settings</CardTitle>
              <CardDescription>Configure global system settings.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <h3 className="text-lg font-medium">General Settings</h3>

                <div className="grid gap-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label htmlFor="maintenance-mode">Maintenance Mode</Label>
                      <p className="text-sm text-muted-foreground">
                        When enabled, only administrators can access the site
                      </p>
                    </div>
                    <Switch id="maintenance-mode" />
                  </div>

                  <div className="flex items-center justify-between">
                    <div>
                      <Label htmlFor="user-registration">User Registration</Label>
                      <p className="text-sm text-muted-foreground">Allow new users to register</p>
                    </div>
                    <Switch id="user-registration" defaultChecked />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="default-user-role">Default User Role</Label>
                    <Select defaultValue="user">
                      <SelectTrigger id="default-user-role">
                        <SelectValue placeholder="Select a role" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="user">User</SelectItem>
                        <SelectItem value="moderator">Moderator</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h3 className="text-lg font-medium">Content Settings</h3>

                <div className="grid gap-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label htmlFor="content-moderation">Content Moderation</Label>
                      <p className="text-sm text-muted-foreground">Automatically moderate content using AI</p>
                    </div>
                    <Switch id="content-moderation" defaultChecked />
                  </div>

                  <div className="flex items-center justify-between">
                    <div>
                      <Label htmlFor="media-uploads">Media Uploads</Label>
                      <p className="text-sm text-muted-foreground">Allow users to upload media files</p>
                    </div>
                    <Switch id="media-uploads" defaultChecked />
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h3 className="text-lg font-medium">System Maintenance</h3>

                <div className="grid gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="database-backup">Database Backup</Label>
                    <div className="flex items-center gap-2">
                      <Button variant="outline" size="sm">
                        Create Backup
                      </Button>
                      <p className="text-xs text-muted-foreground">Last backup: Never</p>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="clear-cache">Clear Cache</Label>
                    <div className="flex items-center gap-2">
                      <Button variant="outline" size="sm">
                        Clear System Cache
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <div className="pt-4 flex justify-end">
                <Button>Save Settings</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This action cannot be undone. This will permanently delete the {itemToDelete?.type}.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isDeleting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={confirmDelete}
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
    </div>
  )
}
