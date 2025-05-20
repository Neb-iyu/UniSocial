"use client"

import type React from "react"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import { Home, Bell, Search, User, PenSquare } from "lucide-react"
import { motion } from "framer-motion"

interface MobileNavProps extends React.HTMLAttributes<HTMLDivElement> {}

export function MobileNav({ className, ...props }: MobileNavProps) {
  const pathname = usePathname()

  const routes = [
    {
      label: "Home",
      icon: Home,
      href: "/feed",
      active: pathname === "/feed",
    },
    {
      label: "Search",
      icon: Search,
      href: "/search",
      active: pathname === "/search",
    },
    {
      label: "New",
      icon: PenSquare,
      href: "/feed?new=post",
      active: false,
      primary: true,
    },
    {
      label: "Notifications",
      icon: Bell,
      href: "/notifications",
      active: pathname === "/notifications",
    },
    {
      label: "Profile",
      icon: User,
      href: "/profile/me",
      active: pathname?.startsWith("/profile"),
    },
  ]

  return (
    <div
      className={cn("fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur-sm", className)}
      {...props}
    >
      <div className="flex items-center justify-around h-16 px-4">
        {routes.map((route) => (
          <Link key={route.href} href={route.href} className="flex-1 flex justify-center">
            <motion.div
              whileTap={{ scale: 0.9 }}
              className={cn(
                "flex flex-col items-center justify-center w-full",
                route.active ? "text-primary" : "text-muted-foreground",
              )}
            >
              {route.primary ? (
                <div className="bg-gradient-to-r from-purple-600 to-blue-500 rounded-full p-3 -mt-8 shadow-lg">
                  <route.icon className="h-6 w-6 text-white" />
                </div>
              ) : (
                <route.icon className="h-5 w-5" />
              )}
              <span className="text-xs mt-1">{route.label}</span>
            </motion.div>
          </Link>
        ))}
      </div>
    </div>
  )
}
