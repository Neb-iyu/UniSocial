import type React from "react"
import { Sidebar } from "@/components/sidebar"
import { MobileNav } from "@/components/mobile-nav"
import { SearchBar } from "@/components/search-bar"
import { Suspense } from "react"

export default function MainLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen">
      <Sidebar className="hidden lg:flex" />

      <main className="flex-1">
        <div className="container max-w-7xl mx-auto px-4 py-6">
          <div className="mb-6 flex justify-center">
            <Suspense>
              <SearchBar />
            </Suspense>
          </div>
          {children}
        </div>
      </main>

      <MobileNav className="lg:hidden" />
    </div>
  )
}
