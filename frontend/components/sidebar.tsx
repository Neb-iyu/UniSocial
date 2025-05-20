"use client"

import type React from "react"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Home, Bell, Search, User, Settings, Users, PenSquare } from "lucide-react"

interface SidebarProps extends React.HTMLAttributes<HTMLDivElement> {}

export function Sidebar({ className, ...props }: SidebarProps) {
  const pathname = usePathname()

  const routes = [
    {
      label: "Home",
      icon: Home,
      href: "/feed",
      active: pathname === "/feed",
    },
    {
      label: "Explore",
      icon: Search,
      href: "/search",
      active: pathname === "/search",
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
    // {
    //   label: "Communities",
    //   icon: Users,
    //   href: "/communities",
    //   active: pathname?.startsWith("/communities"),
    // },
    // {
    //   label: "Settings",
    //   icon: Settings,
    //   href: "/settings",
    //   active: pathname?.startsWith("/settings"),
    // },
  ]

  return (
    <div className={cn("border-r h-screen", className)} {...props}>
      <div className="flex h-full flex-col">
        <div className="flex h-14 items-center border-b px-6">
          <Link href="/feed" className="flex items-center gap-2 font-semibold">
            <span className="font-bold text-xl bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500">
              Unifyze
            </span>
          </Link>
        </div>
        <ScrollArea className="flex-1 px-4 py-6">
          <nav className="flex flex-col gap-2">
            {routes.map((route) => (
              <Link key={route.href} href={route.href}>
                <Button
                  variant={route.active ? "default" : "ghost"}
                  className={cn("w-full justify-start gap-2", route.active ? "" : "hover:bg-muted")}
                >
                  <route.icon className="h-5 w-5" />
                  {route.label}
                </Button>
              </Link>
            ))}
          </nav>
        </ScrollArea>
        <div className="p-4 border-t">
          <Link href="/feed">
            <Button className="w-full gap-2 bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600">
              <PenSquare className="h-5 w-5" />
              New Post
            </Button>
          </Link>
        </div>
      </div>
    </div>
  )
}
