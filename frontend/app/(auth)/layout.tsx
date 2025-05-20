import type React from "react"
import Image from "next/image"
import { ModeToggle } from "@/components/mode-toggle"

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen flex flex-col">
      <header className="border-b py-4 px-6 flex justify-between items-center">
        <div className="flex items-center gap-2">
          <div className="bg-primary rounded-full p-2 flex items-center justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="h-5 w-5 text-primary-foreground"
            >
              <path d="M18 6H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h13l4-3.5L18 6Z" />
              <path d="M12 13v8" />
              <path d="M5 13v6a2 2 0 0 0 2 2h8" />
            </svg>
          </div>
          <h1 className="text-xl font-bold">Unifyze</h1>
        </div>
        <ModeToggle />
      </header>
      <main className="flex-1 flex">
        <div className="hidden lg:flex w-1/2 bg-primary/10 items-center justify-center p-8">
          <div className="max-w-md text-center">
            <h2 className="text-3xl font-bold mb-4">Connect with your university community</h2>
            <p className="text-muted-foreground mb-8">
              Share ideas, collaborate on projects, and stay connected with your peers and faculty.
            </p>
            <div className="relative h-80 w-full">
              <Image
                src="/placeholder.svg?height=400&width=400"
                alt="University social network"
                fill
                className="object-contain"
              />
            </div>
          </div>
        </div>
        <div className="w-full lg:w-1/2">{children}</div>
      </main>
    </div>
  )
}
