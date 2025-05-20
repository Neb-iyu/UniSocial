"use client"

import type React from "react"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import { useAuth } from "@/context/auth-context"

export function MainNav({ className, ...props }: React.HTMLAttributes<HTMLElement>) {
  const pathname = usePathname()
  const { user } = useAuth()

  return (
    <nav className={cn("flex items-center space-x-4 lg:space-x-6", className)} {...props}>
      <Link href="/" className="font-bold text-xl">
        Unifyze
      </Link>
      {user && (
        <>
          <Link
            href="/feed"
            className={cn(
              "text-sm font-medium transition-colors hover:text-primary",
              pathname === "/feed" ? "text-primary" : "text-muted-foreground",
            )}
          >
            Feed
          </Link>
          <Link
            href={`/profile/${user.username}`}
            className={cn(
              "text-sm font-medium transition-colors hover:text-primary",
              pathname === `/profile/${user.username}` ? "text-primary" : "text-muted-foreground",
            )}
          >
            Profile
          </Link>
        </>
      )}
    </nav>
  )
}
