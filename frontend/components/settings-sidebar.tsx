"use client"

import { Button } from "@/components/ui/button"

import Link from "next/link"
import { usePathname } from "next/navigation"

interface SettingsSidebarProps {
  items: {
    title: string
    href: string
  }[]
}

export function SettingsSidebar({ items }: SettingsSidebarProps) {
  const pathname = usePathname()

  return (
    <div className="space-y-4">
      <div className="mb-4">
        <h4 className="text-sm font-medium">Settings</h4>
      </div>
      <div className="flex flex-col space-y-1">
        {items.map((item) => (
          <Link key={item.href} href={item.href}>
            <Button variant={pathname === item.href ? "default" : "ghost"} className="w-full justify-start">
              {item.title}
            </Button>
          </Link>
        ))}
      </div>
    </div>
  )
}
