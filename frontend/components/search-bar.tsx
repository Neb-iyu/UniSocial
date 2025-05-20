"use client"

import type React from "react"

import { useState, useRef, useEffect } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Search, X } from "lucide-react"
import { useDebounce } from "@/hooks/use-debounce"
import { apiService } from "@/lib/api-service"
import { UserAvatar } from "@/components/user-avatar"
import Link from "next/link"
import { motion, AnimatePresence } from "framer-motion"
import type { User } from "@/types/user"
import type { Post } from "@/types/post"

export function SearchBar() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const initialQuery = searchParams?.get("q") || ""

  const [query, setQuery] = useState(initialQuery)
  const [focused, setFocused] = useState(false)
  const [results, setResults] = useState<{ users: User[]; posts: Post[] }>({ users: [], posts: [] })
  const [isLoading, setIsLoading] = useState(false)
  const debouncedQuery = useDebounce(query, 300)
  const inputRef = useRef<HTMLInputElement>(null)
  const resultsRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (debouncedQuery.length >= 2) {
      const search = async () => {
        setIsLoading(true)
        try {
          const [usersResponse, postsResponse] = await Promise.all([
            apiService.get<User[]>(`/search/users?q=${encodeURIComponent(debouncedQuery)}`),
            apiService.get<Post[]>(`/search/posts?q=${encodeURIComponent(debouncedQuery)}`),
          ])

          setResults({
            users: usersResponse || [],
            posts: postsResponse || [],
          })
        } catch (error) {
          console.error("Search error:", error)
        } finally {
          setIsLoading(false)
        }
      }

      search()
    } else {
      setResults({ users: [], posts: [] })
    }
  }, [debouncedQuery])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (query.trim()) {
      router.push(`/search?q=${encodeURIComponent(query)}`)
      setFocused(false)
    }
  }

  const clearSearch = () => {
    setQuery("")
    inputRef.current?.focus()
  }

  // Close results when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        resultsRef.current &&
        !resultsRef.current.contains(event.target as Node) &&
        inputRef.current &&
        !inputRef.current.contains(event.target as Node)
      ) {
        setFocused(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
    }
  }, [])

  const hasResults = results.users.length > 0 || results.posts.length > 0

  return (
    <div className="relative w-full max-w-md mx-auto">
      <form onSubmit={handleSubmit} className="relative">
        <Input
          ref={inputRef}
          type="search"
          placeholder="Search people, posts, and more..."
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onFocus={() => setFocused(true)}
          className="pl-10 pr-10 h-10 rounded-full border-muted-foreground/20 focus-visible:ring-primary/50"
        />
        <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
        {query && (
          <Button
            type="button"
            variant="ghost"
            size="sm"
            className="absolute right-2 top-2 h-6 w-6 p-0 rounded-full"
            onClick={clearSearch}
          >
            <X className="h-4 w-4" />
            <span className="sr-only">Clear search</span>
          </Button>
        )}
      </form>

      <AnimatePresence>
        {focused && query.length >= 2 && (
          <motion.div
            ref={resultsRef}
            className="absolute top-full mt-2 w-full rounded-xl border bg-background shadow-lg overflow-hidden z-50"
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
          >
            {isLoading ? (
              <div className="p-4 text-center">
                <div className="loader mx-auto"></div>
                <p className="text-sm text-muted-foreground mt-2">Searching...</p>
              </div>
            ) : hasResults ? (
              <div className="max-h-80 overflow-auto">
                {results.users.length > 0 && (
                  <div className="p-2">
                    <h3 className="px-2 py-1.5 text-sm font-medium text-muted-foreground">People</h3>
                    <div className="space-y-1">
                      {results.users.slice(0, 5).map((user) => (
                        <Link
                          key={user.public_uuid}
                          href={`/profile/${user.username}`}
                          onClick={() => setFocused(false)}
                          className="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-muted"
                        >
                          <UserAvatar user={user} size="sm" />
                          <div>
                            <p className="font-medium">{user.fullname || user.username}</p>
                            <p className="text-xs text-muted-foreground">@{user.username}</p>
                          </div>
                        </Link>
                      ))}
                      {results.users.length > 5 && (
                        <Link
                          href={`/search?q=${encodeURIComponent(query)}&type=users`}
                          onClick={() => setFocused(false)}
                          className="block px-2 py-1.5 text-sm text-primary hover:underline"
                        >
                          View all {results.users.length} people
                        </Link>
                      )}
                    </div>
                  </div>
                )}

                {results.posts.length > 0 && (
                  <div className="p-2 border-t">
                    <h3 className="px-2 py-1.5 text-sm font-medium text-muted-foreground">Posts</h3>
                    <div className="space-y-1">
                      {results.posts.slice(0, 3).map((post) => (
                        <Link
                          key={post.public_uuid}
                          href={`/post/${post.public_uuid}`}
                          onClick={() => setFocused(false)}
                          className="flex items-start gap-3 px-2 py-1.5 rounded-lg hover:bg-muted"
                        >
                          <UserAvatar user={post.user} size="sm" />
                          <div>
                            <p className="font-medium">{post.user?.username}</p>
                            <p className="text-sm line-clamp-2">{post.content}</p>
                          </div>
                        </Link>
                      ))}
                      {results.posts.length > 3 && (
                        <Link
                          href={`/search?q=${encodeURIComponent(query)}&type=posts`}
                          onClick={() => setFocused(false)}
                          className="block px-2 py-1.5 text-sm text-primary hover:underline"
                        >
                          View all {results.posts.length} posts
                        </Link>
                      )}
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <div className="p-4 text-center">
                <p className="text-sm text-muted-foreground">No results found for "{query}"</p>
              </div>
            )}

            <div className="p-2 border-t">
              <Button variant="ghost" className="w-full justify-center text-primary" onClick={handleSubmit}>
                Search for "{query}"
                <Search className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
